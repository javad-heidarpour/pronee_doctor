<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Appointment;
use App\Models\HealthExchangeMessage;
use App\Models\Patient;
use App\Models\PatientInsuranceVerification;
use App\Models\PatientEncounter;
use App\Services\HealthInterop\HealthExchangeOutbox;
use App\Services\HealthInterop\IranianNationalCode;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class HealthInteropController extends Controller
{
    use HasActiveDoctor;

    public function index(RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.view', $doctor);

        // The dashboard contains patient insurance and exchange status; audit access without payload data.
        $audit->handle('health_interop.dashboard.viewed', null, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/HealthInterop/Index', [
            // پیام خام خطا و جزئیات پاسخ بیمه ممکن است شامل URL داخلی، شناسه یا داده حساس باشد؛
            // در پنل فقط وضعیت و شناسه پیگیری نمایش داده می‌شود و متن خطا در سرور می‌ماند.
            'messages' => HealthExchangeMessage::query()->where('doctor_id', $doctor->id)->latest()->limit(100)->get(['id', 'destination', 'event_type', 'status', 'attempts', 'sent_at', 'remote_tracking_id', 'created_at']),
            'insuranceChecks' => PatientInsuranceVerification::query()->where('doctor_id', $doctor->id)->latest('id')->limit(50)->get(['id', 'patient_id', 'payer_code', 'payer_name', 'status', 'checked_at', 'created_at']),
            // وضعیت اتصال فقط وقتی آماده است که قرارداد، URL و allowlist میزبان همگی تنظیم باشند.
            'destinations' => collect(['sepas', 'tamin', 'salamat'])->mapWithKeys(fn ($key) => [$key => [
                'enabled' => (bool) config("health_interop.destinations.{$key}.enabled") && (bool) config("health_interop.destinations.{$key}.contract_approved") && filled(config("health_interop.destinations.{$key}.url"))
                    && !empty(config("health_interop.destinations.{$key}.allowed_hosts")),
            ]]),
        ]);
    }

    public function validateIdentity(Request $request, Patient $patient, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.view', $doctor);
        abort_unless((int) $patient->doctor_id === (int) $doctor->id, 404);
        $valid = filled($patient->national_code) && IranianNationalCode::isValid($patient->national_code);
        $audit->handle('health_interop.patient_identity.checked', $patient, ['doctor_id' => $doctor->id]);

        return response()->json([
            'structural_valid' => $valid,
            'identity_verified' => false,
            'message' => $valid
                ? 'ساختار کد ملی معتبر است؛ احراز هویت رسمی هنوز انجام نشده است.'
                : 'کد ملی از نظر ساختار یا رقم کنترلی معتبر نیست.',
        ], 200);
    }

    public function verifyInsurance(Request $request, Patient $patient, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        abort_unless((int) $patient->doctor_id === (int) $doctor->id, 404);

        $data = $request->validate([
            'payer_code' => ['required', 'string', Rule::in(['tamin', 'salamat', 'armed_forces', 'other'])],
            'payer_name' => ['nullable', 'string', 'max:120'],
            'policy_number' => ['nullable', 'string', 'max:100'],
            'member_number' => ['nullable', 'string', 'max:100'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
        ]);
        if (!empty($data['appointment_id'])) {
            Appointment::query()
                ->whereKey($data['appointment_id'])
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->firstOrFail();
        }

        $enabled = (bool) config("health_interop.destinations.{$data['payer_code']}.enabled")
            && (bool) config("health_interop.destinations.{$data['payer_code']}.contract_approved")
            && filled(config("health_interop.destinations.{$data['payer_code']}.url"))
            && !empty(config("health_interop.destinations.{$data['payer_code']}.allowed_hosts"));

        $record = PatientInsuranceVerification::query()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_id' => $data['appointment_id'] ?? null,
            'payer_code' => $data['payer_code'],
            'payer_name' => $data['payer_name'] ?? null,
            'policy_number' => $data['policy_number'] ?? null,
            'member_number' => $data['member_number'] ?? null,
            'status' => $enabled ? 'awaiting_integration' : 'not_configured',
            'checked_at' => null,
            'failure_message' => $enabled
                ? 'اتصال فعال است؛ عملیات استعلام تا زمان پیاده‌سازی قرارداد رسمی این بیمه‌گر قابل اجرا نیست.'
                : 'اتصال رسمی این بیمه‌گر هنوز پیکربندی نشده است.',
        ]);

        $audit->handle('health_interop.insurance_verification.created', $record, ['doctor_id' => $doctor->id]);

        return response()->json([
            'id' => $record->id,
            'status' => $record->status,
            'message' => $record->failure_message,
            'insurance_approved' => false,
        ], 202);
    }

    public function queueAppointment(Appointment $appointment, HealthExchangeOutbox $outbox, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.manage', $doctor);
        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 404);
        abort_unless($appointment->patient_id && $appointment->status !== 'cancelled', 422);
        $message = $outbox->enqueueAppointment($appointment);
        $audit->handle('health_interop.appointment.queued', $appointment, ['doctor_id' => $doctor->id]);
        return back()->with('success', 'رویداد نوبت در صف تبادل قرار گرفت؛ پذیرش مقصد هنوز تأیید نشده است.');
    }

    public function queueEncounter(PatientEncounter $encounter, HealthExchangeOutbox $outbox, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        abort_unless((int) $encounter->doctor_id === (int) $doctor->id, 404);
        abort_unless($encounter->status === PatientEncounter::STATUS_SIGNED, 409);
        $outbox->enqueueEncounter($encounter);
        $audit->handle('health_interop.encounter.queued', $encounter, ['doctor_id' => $doctor->id]);
        return back()->with('success', 'ویزیت و نسخه‌های آن در صف تبادل قرار گرفتند.');
    }
}
