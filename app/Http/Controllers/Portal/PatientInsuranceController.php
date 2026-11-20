<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Patient;
use App\Models\PatientInsuranceVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

final class PatientInsuranceController extends Controller
{
    use HasActiveDoctor;

    public function index(Patient $patient)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403);

        $patient = Patient::query()->whereKey($patient->id)
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($query) => $query->whereIn('clinic_id', $clinicIds))
            ->firstOrFail();

        $verifications = PatientInsuranceVerification::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patient->id)
            ->orderByDesc('checked_at')->orderByDesc('id')
            ->paginate(15)->withQueryString()
            ->through(fn (PatientInsuranceVerification $item): array => [
                'id' => $item->id,
                'payer_code' => $item->payer_code,
                'payer_name' => $item->payer_name,
                'policy_number_masked' => $this->maskIdentifier($item->policy_number),
                'member_number_masked' => $this->maskIdentifier($item->member_number),
                'status' => $item->status,
                'checked_at' => $item->checked_at?->toIso8601String(),
                'valid_from' => $item->valid_from?->toDateString(),
                'valid_until' => $item->valid_until?->toDateString(),
                'tracking_id' => $item->tracking_id,
                'failure_message' => $item->failure_message,
            ]);

        return Inertia::render('Portal/Patients/Insurance', [
            'doctor' => $doctor,
            'patient' => ['id' => $patient->id, 'name' => $patient->name],
            'verifications' => $verifications,
            'payers' => [
                ['code' => 'tamin', 'name' => 'تأمین اجتماعی'],
                ['code' => 'salamat', 'name' => 'بیمه سلامت'],
                ['code' => 'armed_forces', 'name' => 'نیروهای مسلح'],
                ['code' => 'other', 'name' => 'سایر'],
            ],
        ]);
    }

    public function store(
        Request $request,
        Patient $patient,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403);

        $patient = Patient::query()->whereKey($patient->id)
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($query) => $query->whereIn('clinic_id', $clinicIds))
            ->firstOrFail();

        $data = $request->validate([
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')->where('doctor_id', $doctor->id)->where('patient_id', $patient->id)],
            'payer_code' => ['required', Rule::in(['tamin', 'salamat', 'armed_forces', 'other'])],
            'payer_name' => ['required', 'string', 'max:120'],
            'policy_number' => ['nullable', 'string', 'max:120'],
            'member_number' => ['nullable', 'string', 'max:120'],
            'status' => ['required', Rule::in(['verified', 'rejected', 'pending', 'not_checked'])],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'tracking_id' => ['nullable', 'string', 'max:120'],
            'failure_code' => ['nullable', 'string', 'max:80'],
            'failure_message' => ['nullable', 'string', 'max:1000'],
        ]);

        if (!empty($data['appointment_id'])) {
            $appointment = DB::table('appointments')
                ->where('id', $data['appointment_id'])
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->whereIn('clinic_id', $clinicIds)
                ->first();
            abort_unless($appointment, 404);
        }

        if ($data['status'] === 'verified') {
            if (empty($data['valid_until']) || empty($data['policy_number'])) {
                throw ValidationException::withMessages([
                    'policy_number' => 'برای ثبت پوشش تأییدشده، شماره بیمه‌نامه الزامی است.',
                    'valid_until' => 'برای ثبت پوشش تأییدشده، تاریخ پایان اعتبار الزامی است.',
                ]);
            }
        }

        $verification = DB::transaction(fn () => PatientInsuranceVerification::query()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'appointment_id' => $data['appointment_id'] ?? null,
            'payer_code' => trim($data['payer_code']),
            'payer_name' => trim($data['payer_name']),
            'policy_number' => $data['policy_number'] ?? null,
            'member_number' => $data['member_number'] ?? null,
            'status' => $data['status'],
            'checked_at' => in_array($data['status'], ['verified', 'rejected'], true) ? now() : null,
            'valid_from' => $data['valid_from'] ?? null,
            'valid_until' => $data['valid_until'] ?? null,
            'tracking_id' => $data['tracking_id'] ?? null,
            'failure_code' => $data['failure_code'] ?? null,
            'failure_message' => $data['failure_message'] ?? null,
            'response_payload' => [
                'verification_source' => 'manual',
                'recorded_by_user_id' => (int) Auth::guard('web')->id(),
            ],
        ]), 3);

        $audit->handle('patient.insurance.verification_recorded', $verification, [
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'verification_id' => $verification->id,
            'status' => $verification->status,
            'payer_code' => $verification->payer_code,
            'changed_fields' => ['insurance_verification'],
        ]);

        return back()->with('success', 'وضعیت بیمه به‌صورت دستی ثبت شد؛ این ثبت به معنی استعلام خودکار از شرکت بیمه نیست.');
    }
    private function maskIdentifier(?string $value): ?string
    {
        if (!$value) return null;
        $digits = preg_replace('/\s+/', '', $value);
        return mb_strlen($digits) <= 4 ? str_repeat('•', mb_strlen($digits)) : str_repeat('•', max(4, mb_strlen($digits) - 4)) . mb_substr($digits, -4);
    }
}
