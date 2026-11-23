<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Address;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\PatientFollowUpTemplate;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreatePatientFollowUpAction
{
    public function handle(Doctor $doctor, User $actor, array $data, array $accessibleClinicIds): Collection
    {
        $accessibleClinicIds = array_values(array_unique(array_map('intval', $accessibleClinicIds)));

        return DB::transaction(function () use ($doctor, $actor, $data, $accessibleClinicIds): Collection {
            $patient = Patient::query()
                ->whereKey($data['patient_id'])
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Resolve the appointment and clinic from one locked row. Separate
            // existence and clinic lookups could otherwise observe different
            // appointment ownership during concurrent booking changes.
            $appointmentQuery = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->whereIn('clinic_id', $accessibleClinicIds);

            if (!empty($data['appointment_id'])) {
                $appointment = (clone $appointmentQuery)
                    ->whereKey($data['appointment_id'])
                    ->lockForUpdate()
                    ->first();
            } else {
                $appointment = (clone $appointmentQuery)
                    ->orderByDesc('appointment_date')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();
            }

            if (!$appointment) {
                throw ValidationException::withMessages([
                    !empty($data['appointment_id']) ? 'appointment_id' : 'patient_id' =>
                        !empty($data['appointment_id'])
                            ? 'نوبت انتخاب‌شده متعلق به این بیمار و پزشک نیست یا در مطب مجاز قرار ندارد.'
                            : 'برای این بیمار در مطب‌های مجاز شما سابقه مراجعه‌ای یافت نشد.',
                ]);
            }

            $clinicId = (int) $appointment->clinic_id;

            Address::query()
                ->whereKey($clinicId)
                ->where('doctor_id', $doctor->id)
                ->where('is_active', true)
                ->firstOrFail();

            $template = null;
            if (!empty($data['template_id'])) {
                $template = PatientFollowUpTemplate::query()
                    ->whereKey($data['template_id'])
                    ->where('doctor_id', $doctor->id)
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $assigneeId = $data['assigned_to'] ?? null;
            if ($assigneeId !== null) {
                $secretary = $doctor->secretaries()
                    ->where('users.id', $assigneeId)
                    ->where('users.is_active', true)
                    ->lockForUpdate()
                    ->first();

                $secretaryClinicIds = $secretary?->pivot?->clinic_ids ?? [];
                if (is_string($secretaryClinicIds)) {
                    $secretaryClinicIds = json_decode($secretaryClinicIds, true) ?: [];
                }

                if (
                    !$secretary ||
                    !is_array($secretaryClinicIds) ||
                    !in_array($clinicId, array_map('intval', $secretaryClinicIds), true)
                ) {
                    throw ValidationException::withMessages([
                        'assigned_to' => 'مسئول انتخاب‌شده، منشی فعال و متصل به این مطب نیست.',
                    ]);
                }
            }

            $planUuid = (string) Str::uuid();
            $steps = $data['steps'] ?? [[
                'title' => $template?->title ?? $data['title'],
                'days' => 0,
                'contact_method' => $template?->contact_method ?? ($data['contact_method'] ?? 'call'),
                'send_sms' => ((bool) ($data['send_sms'] ?? false) || (bool) ($template?->send_sms ?? false))
                    && (bool) ($data['sms_consent_confirmed'] ?? false),
                'sms_consent_confirmed' => (bool) ($data['sms_consent_confirmed'] ?? false),
            ]];

            $baseDate = Carbon::parse($data['due_at']);
            $tasks = collect();

            foreach ($steps as $step) {
                $tasks->push(PatientFollowUpTask::create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinicId,
                    'patient_id' => $patient->id,
                    'appointment_id' => !empty($data['appointment_id']) ? $appointment->id : null,
                    'template_id' => $template?->id,
                    'assigned_to' => $assigneeId,
                    'created_by' => $actor->id,
                    'plan_uuid' => $planUuid,
                    'title' => $step['title'],
                    'description' => $template?->description ?? ($data['description'] ?? null),
                    'due_at' => $baseDate->copy()->addDays((int) ($step['days'] ?? 0)),
                    'priority' => $data['priority'] ?? 'normal',
                    'contact_method' => $step['contact_method'] ?? 'call',
                    'send_sms' => (bool) ($step['send_sms'] ?? false)
                        && (bool) ($step['sms_consent_confirmed'] ?? false),
                    'sms_consent_confirmed' => (bool) ($step['sms_consent_confirmed'] ?? false),
                    'status' => 'pending',
                ]));
            }

            return $tasks;
        });
    }
}
