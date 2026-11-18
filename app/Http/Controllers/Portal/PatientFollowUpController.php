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
use App\Models\PatientFollowUpTemplate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PatientFollowUpController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view', $doctor);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(['pending', 'completed', 'unsuccessful', 'cancelled'])],
            'scope' => ['nullable', Rule::in(['all', 'overdue', 'today', 'mine'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $clinicIds = $this->accessibleClinicIds($doctor);
        $query = PatientFollowUpTask::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->with(['patient:id,name,phone', 'clinic:id,name', 'assignee:id,name,family', 'appointment:id,appointment_date,appointment_time'])
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when(($filters['scope'] ?? null) === 'overdue', fn ($q) => $q->overdue())
            ->when(($filters['scope'] ?? null) === 'today', fn ($q) => $q->whereDate('due_at', today())->where('status', 'pending'))
            ->when(($filters['scope'] ?? null) === 'mine', fn ($q) => $q->where('assigned_to', Auth::guard('web')->id()))
            ->when($filters['search'] ?? null, function ($q, $search): void {
                $q->whereHas('patient', fn ($patient) => $patient->where('name', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
            });

        $tasks = $query->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('due_at')->paginate(20)->withQueryString();

        $patients = Patient::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))
            ->orderBy('name')->get(['id', 'name', 'phone']);

        $templates = PatientFollowUpTemplate::query()
            ->where('doctor_id', $doctor->id)->where('is_active', true)->orderBy('title')->get();

        $secretaries = $doctor->secretaries()->where('users.is_active', true)
            ->orderBy('users.name')->get(['users.id', 'users.name', 'users.family']);

        $base = PatientFollowUpTask::query()->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds);

        return Inertia::render('Portal/FollowUps/Index', [
            'doctor' => $doctor,
            'userRole' => Auth::guard('web')->user()?->role,
            'tasks' => $tasks,
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
}