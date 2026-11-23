<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Patient\CreatePatientFollowUpAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\StorePatientFollowUpRequest;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\Doctor;
use App\Models\PatientFollowUpTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PatientFollowUpController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view', $doctor);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'completed', 'unsuccessful', 'cancelled'])],
            'scope' => ['nullable', Rule::in(['all', 'overdue', 'today', 'mine'])],
            'search' => ['nullable', 'string', 'max:100'],
            'patient_id' => ['nullable', 'integer'],
            'appointment_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($request): void {
                    if ($value !== null && $value !== '' && !$request->filled('patient_id')) {
                        $fail('برای انتخاب نوبت، ابتدا بیمار را مشخص کنید.');
                    }
                },
            ],
        ]);

        $clinicIds = $this->accessibleClinicIds($doctor);
        $selectedPatientId = null;
        $selectedAppointmentId = null;

        if (!empty($filters['patient_id'])) {
            $selectedPatientId = Patient::query()
                ->whereKey($filters['patient_id'])
                ->where('doctor_id', $doctor->id)
                ->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))
                ->value('id');
            abort_unless($selectedPatientId, 404);

            if (!empty($filters['appointment_id'])) {
                $selectedAppointmentId = $doctor->appointments()
                    ->whereKey($filters['appointment_id'])
                    ->where('patient_id', $selectedPatientId)
                    ->whereIn('clinic_id', $clinicIds)
                    ->value('id');
                abort_unless($selectedAppointmentId, 404);
            }
        }

        $query = PatientFollowUpTask::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->when($selectedPatientId !== null, fn ($q) => $q->where('patient_id', $selectedPatientId))
            ->when($selectedAppointmentId !== null, fn ($q) => $q->where('appointment_id', $selectedAppointmentId))
            ->with(['patient:id,name,phone', 'clinic:id,name', 'assignee:id,name,family', 'appointment:id,appointment_date,appointment_time'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(($filters['scope'] ?? null) === 'overdue', fn ($q) => $q->overdue())
            ->when(($filters['scope'] ?? null) === 'today', fn ($q) => $q->whereDate('due_at', today())->where('status', 'pending'))
            ->when(($filters['scope'] ?? null) === 'mine', fn ($q) => $q->where('assigned_to', Auth::guard('web')->id()))
            ->when($filters['search'] ?? null, function ($q, $search): void {
                $q->whereHas('patient', fn ($patient) => $patient->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });

        $canViewClinicalNotes = $this->canViewClinicalNotes($doctor);
        $tasks = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('due_at')->paginate(20)->withQueryString();
        $tasks->getCollection()->transform(
            fn (PatientFollowUpTask $task): array => $this->serializeTask($task, $canViewClinicalNotes)
        );

        $patients = Patient::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))
            ->orderBy('name')->get(['id', 'name', 'phone']);

        // Templates are only used by the doctor/admin editor and may contain free text.
        $templates = Auth::guard('web')->user()?->role === 'secretary'
            ? collect()
            : PatientFollowUpTemplate::query()
                ->where('doctor_id', $doctor->id)->where('is_active', true)->orderBy('title')->get();

        // Do not serialize the doctor_user pivot (permissions and clinic assignments).
        $secretaries = $doctor->secretaries()->where('users.is_active', true)
            ->orderBy('users.name')->get(['users.id', 'users.name', 'users.family'])
            ->map(fn (User $secretary): array => [
                'id' => $secretary->id,
                'name' => $secretary->name,
                'family' => $secretary->family,
            ])->values();

        $base = PatientFollowUpTask::query()->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds);

        // The follow-up list contains patient and care-coordination details.
        $audit->handle('patient.follow_ups.listed', null, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/FollowUps/Index', [
            'doctor' => $doctor,
            'userRole' => Auth::guard('web')->user()?->role,
            'selectedPatientId' => $selectedPatientId,
            'selectedAppointmentId' => $selectedAppointmentId,
            'tasks' => $tasks,
            'canViewClinicalNotes' => $canViewClinicalNotes,
            'patients' => $patients,
            'templates' => $templates,
            'secretaries' => $secretaries,
            'filters' => array_merge(['status' => '', 'scope' => 'all', 'search' => ''], $filters),
            'stats' => [
                'pending' => (clone $base)->where('status', 'pending')->count(),
                'overdue' => (clone $base)->overdue()->count(),
                'today' => (clone $base)->whereDate('due_at', today())->where('status', 'pending')->count(),
                'completed' => (clone $base)->where('status', 'completed')->count(),
                'unsuccessful' => (clone $base)->where('status', 'unsuccessful')->count(),
            ],
        ]);
    }

    public function store(
        StorePatientFollowUpRequest $request,
        CreatePatientFollowUpAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.manage', $doctor);

        $tasks = $action->handle($doctor, Auth::guard('web')->user(), $request->validated(), $this->accessibleClinicIds($doctor));

        foreach ($tasks as $task) {
            $audit->handle('patient.follow_up.created', $task, [
                'doctor_id' => $doctor->id,
                'changed_fields' => ['follow_up_created'],
            ]);
        }

        return back()->with('success', count($tasks) . ' گام از برنامه پیگیری بیمار ثبت شد.');
    }

    public function update(Request $request, int $task, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.manage', $doctor);

        $followUp = PatientFollowUpTask::query()
            ->whereKey($task)->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))->firstOrFail();

        $data = $request->validate([
            'status' => ['required', Rule::in(['pending', 'completed', 'unsuccessful', 'cancelled'])],
            'outcome' => ['nullable', Rule::in(['reached', 'no_answer', 'rescheduled', 'referred', 'needs_visit', 'other'])],
            'result_notes' => ['nullable', 'string', 'max:3000'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date'],
        ]);

        // Follow-up operators without clinical-note permission may update workflow state,
        // but must not write free-text clinical results.
        // Viewing a clinical note is not permission to change it. Keep workflow
        // updates available to patients.manage operators, but only allow free-text
        // clinical results for clinical managers (or the existing appointment
        // management compatibility grant).
        if (!$this->canManageClinicalNotes($doctor)) {
            unset($data['result_notes']);
        }

        if (array_key_exists('assigned_to', $data) && $data['assigned_to'] !== null) {
            $secretary = $doctor->secretaries()->where('users.id', $data['assigned_to'])
                ->where('users.is_active', true)->first();
            $secretaryClinicIds = $secretary?->pivot?->clinic_ids ?? [];
            if (is_string($secretaryClinicIds)) {
                $secretaryClinicIds = json_decode($secretaryClinicIds, true) ?: [];
            }
            abort_unless(
                $secretary && is_array($secretaryClinicIds) &&
                in_array((int) $followUp->clinic_id, array_map('intval', $secretaryClinicIds), true),
                422,
                'مسئول پیگیری به مطب این برنامه دسترسی ندارد.'
            );
        }

        $followUp->fill($data);
        if ($data['status'] === 'completed' || $data['status'] === 'unsuccessful') {
            $followUp->completed_at = now();
        } elseif ($data['status'] === 'pending') {
            $followUp->completed_at = null;
        }
        $followUp->save();

        $audit->handle('patient.follow_up.updated', $followUp, [
            'doctor_id' => $doctor->id,
            'status_changed' => true,
            'changed_fields' => array_keys($data),
        ]);

        return back()->with('success', 'وضعیت پیگیری به‌روزرسانی شد.');
    }

    public function storeTemplate(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.manage', $doctor);
        abort_if(Auth::guard('web')->user()?->role === 'secretary', 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(['general', 'lab', 'chronic', 'surgery', 'post_op', 'cosmetic'])],
            'description' => ['nullable', 'string', 'max:3000'],
            'default_days' => ['required', 'integer', 'min:0', 'max:365'],
            'contact_method' => ['required', Rule::in(['call', 'sms', 'in_person', 'review'])],
            'send_sms' => ['boolean'],
        ]);

        $template = PatientFollowUpTemplate::create($data + ['doctor_id' => $doctor->id]);
        $audit->handle('patient.follow_up_template.created', $template, ['doctor_id' => $doctor->id]);

        return back()->with('success', 'الگوی پیگیری ذخیره شد.');
    }

    public function updateTemplate(Request $request, int $template, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.manage', $doctor);
        abort_if(Auth::guard('web')->user()?->role === 'secretary', 403);

        $model = PatientFollowUpTemplate::query()->whereKey($template)
            ->where('doctor_id', $doctor->id)->firstOrFail();

        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', Rule::in(['general', 'lab', 'chronic', 'surgery', 'post_op', 'cosmetic'])],
            'description' => ['nullable', 'string', 'max:3000'],
            'default_days' => ['required', 'integer', 'min:0', 'max:365'],
            'contact_method' => ['required', Rule::in(['call', 'sms', 'in_person', 'review'])],
            'send_sms' => ['boolean'],
        ]);

        $model->update($data);
        $audit->handle('patient.follow_up_template.updated', $model, ['doctor_id' => $doctor->id]);

        return back()->with('success', 'الگوی پیگیری به‌روزرسانی شد.');
    }

    public function destroyTemplate(int $template, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.manage', $doctor);
        abort_if(Auth::guard('web')->user()?->role === 'secretary', 403);

        $model = PatientFollowUpTemplate::query()->whereKey($template)
            ->where('doctor_id', $doctor->id)->firstOrFail();
        $model->update(['is_active' => false]);
        $audit->handle('patient.follow_up_template.disabled', $model, ['doctor_id' => $doctor->id]);

        return back()->with('success', 'الگو غیرفعال شد؛ سوابق قبلی حفظ شدند.');
    }
    private function canManageClinicalNotes(Doctor $doctor): bool
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'secretary') {
            return true;
        }

        $assignment = $user->doctors()
            ->where('doctors.id', $doctor->id)
            ->first();

        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        if (!is_array($permissions)) {
            return false;
        }

        return in_array('patients.clinical.manage', $permissions, true)
            || (
                in_array('appointments.manage', $permissions, true)
                && in_array('patients.manage', $permissions, true)
            );
    }

    /**
     * Secretary follow-up screens need workflow metadata, not unrestricted clinical text.
     * Mirror the appointment-note permission rule used by the patient portal.
     */
    private function canViewClinicalNotes(Doctor $doctor): bool
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'secretary') {
            return true;
        }

        $assignment = $user->doctors()
            ->where('doctors.id', $doctor->id)
            ->first();

        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        if (!is_array($permissions)) {
            return false;
        }

        return in_array('patients.clinical.view', $permissions, true)
            || in_array('patients.clinical.manage', $permissions, true)
            || (
                in_array('appointments.manage', $permissions, true)
                && in_array('patients.manage', $permissions, true)
            );
    }

    /**
     * Explicit allow-list prevents internal SMS provider errors, retry metadata,
     * pivot data and unneeded identifiers from reaching the browser.
     */
    private function serializeTask(PatientFollowUpTask $task, bool $canViewClinicalNotes): array
    {
        $payload = [
            'id' => $task->id,
            'title' => $canViewClinicalNotes ? $task->title : 'پیگیری بیمار',
            'due_at' => $task->due_at?->toIso8601String(),
            'priority' => $task->priority,
            'contact_method' => $task->contact_method,
            'send_sms' => (bool) $task->send_sms,
            'sms_sent_at' => $task->sms_sent_at?->toIso8601String(),
            'sms_failed' => $task->last_sms_error !== null && $task->last_sms_error !== '',
            'status' => $task->status,
            'outcome' => $task->outcome,
            'assigned_to' => $task->assigned_to,
            'completed_at' => $task->completed_at?->toIso8601String(),
            'patient' => $task->patient ? [
                'id' => $task->patient->id,
                'name' => $task->patient->name,
                'phone' => $task->patient->phone,
            ] : null,
            'clinic' => $task->clinic ? [
                'id' => $task->clinic->id,
                'name' => $task->clinic->name,
            ] : null,
            'assignee' => $task->assignee ? [
                'id' => $task->assignee->id,
                'name' => $task->assignee->name,
                'family' => $task->assignee->family,
            ] : null,
            'appointment' => $task->appointment ? [
                'id' => $task->appointment->id,
                'appointment_date' => $task->appointment->appointment_date,
                'appointment_time' => $task->appointment->appointment_time,
            ] : null,
        ];

        if ($canViewClinicalNotes) {
            $payload['description'] = $task->description;
            $payload['result_notes'] = $task->result_notes;
        }

        return $payload;
    }

}