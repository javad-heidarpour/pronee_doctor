<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientAccountAccess;
use App\Models\PatientConsent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class PatientConsentController extends Controller
{
    use HasActiveDoctor;

    private const TYPES = ['surgery', 'anesthesia', 'treatment', 'data_processing', 'telehealth', 'other'];

    public function index(int $patient, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryConsentView($doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403);

        $patientModel = Patient::query()
            ->whereKey($patient)
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($query) => $query->whereIn('clinic_id', $clinicIds))
            ->firstOrFail();

        $consents = PatientConsent::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patientModel->id)
            ->whereIn('clinic_id', $clinicIds)
            ->with(['clinic:id,name', 'creator:id,name,family', 'signer:id,name,family'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $audit->handle('patient.consents.viewed', null, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/Patients/Consents', [
            'doctor' => $doctor,
            'patient' => ['id' => $patientModel->id, 'name' => $patientModel->name],
            'consents' => $consents,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->orderBy('name')->get(['id', 'name']),
            'canManage' => Auth::guard('web')->user()?->role !== 'secretary'
                || $this->secretaryCanManage($doctor),
            'types' => [
                ['value' => 'surgery', 'label' => 'جراحی'],
                ['value' => 'anesthesia', 'label' => 'بیهوشی'],
                ['value' => 'treatment', 'label' => 'اقدام درمانی'],
                ['value' => 'data_processing', 'label' => 'پردازش اطلاعات سلامت'],
                ['value' => 'telehealth', 'label' => 'خدمات از راه دور'],
                ['value' => 'other', 'label' => 'سایر'],
            ],
        ]);
    }

    public function store(Request $request, int $patient, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403);

        $data = $request->validate([
            'clinic_id' => [
                'required', 'integer',
                Rule::exists('addresses', 'id')->where('doctor_id', $doctor->id)->where('is_active', true),
            ],
            'appointment_id' => [
                'nullable', 'integer',
                Rule::exists('appointments', 'id')->where('doctor_id', $doctor->id)->where('patient_id', $patient),
            ],
            'consent_type' => ['required', Rule::in(self::TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'consent_text' => ['required', 'string', 'min:40', 'max:20000'],
        ]);

        $clinicId = (int) $data['clinic_id'];
        abort_unless(in_array($clinicId, $clinicIds, true), 403);

        $consent = DB::transaction(function () use ($patient, $doctor, $clinicId, $data): PatientConsent {
            $patientModel = Patient::query()
                ->whereKey($patient)
                ->where('doctor_id', $doctor->id)
                ->whereHas('appointments', fn ($query) => $query->where('clinic_id', $clinicId))
                ->lockForUpdate()
                ->firstOrFail();

            if (!PatientAccountAccess::query()->active()
                ->where('patient_id', $patientModel->id)
                ->where('access_type', 'owner')
                ->exists()) {
                abort(422, 'برای امضای الکترونیکی ابتدا باید حساب مالک بیمار فعال باشد.');
            }

            $appointmentId = $data['appointment_id'] ?? null;
            if ($appointmentId) {
                Appointment::query()
                    ->whereKey($appointmentId)
                    ->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patientModel->id)
                    ->where('clinic_id', $clinicId)
                    ->firstOrFail();
            }

            $text = trim($data['consent_text']);

            return PatientConsent::query()->create([
                'patient_id' => $patientModel->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinicId,
                'appointment_id' => $appointmentId,
                'created_by_user_id' => Auth::guard('web')->id(),
                'consent_type' => $data['consent_type'],
                'title' => trim($data['title']),
                'consent_text' => $text,
                'content_sha256' => hash('sha256', $text),
                'status' => PatientConsent::STATUS_PENDING,
            ]);
        }, 3);

        $audit->handle('patient.consent.created', $consent, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['consent_created'],
        ]);

        return back()->with('success', 'رضایت‌نامه برای امضای بیمار ثبت شد.');
    }

    private function authorizeSecretaryConsentView(\App\Models\Doctor $doctor): void
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'secretary') {
            return;
        }

        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        abort_unless(
            is_array($permissions)
                && (in_array('patients.clinical.view', $permissions, true)
                    || in_array('patients.clinical.manage', $permissions, true)),
            403,
            'مجوز مشاهده رضایت‌نامه‌های بالینی برای شما فعال نیست.'
        );
    }

    private function secretaryCanManage(\App\Models\Doctor $doctor): bool
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'secretary') {
            return false;
        }

        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return is_array($permissions) && in_array('patients.clinical.manage', $permissions, true);
    }
}
