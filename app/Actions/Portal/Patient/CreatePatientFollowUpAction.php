<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\PatientTreatmentPlan;
use App\Models\PatientFollowUpTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Collection;
use Carbon\Carbon;

final class CreatePatientFollowUpAction
{
    public function handle(Doctor $doctor, User $actor, array $data, array $accessibleClinicIds): Collection
    {
        return DB::transaction(function () use ($doctor, $actor, $data, $accessibleClinicIds): Collection {
            $patient = Patient::query()
                ->whereKey($data['patient_id'])
                ->where('doctor_id', $doctor->id)
                ->firstOrFail();

            $hasAccessibleVisit = $patient->appointments()
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $accessibleClinicIds)
                ->exists();

            if (!$hasAccessibleVisit) {
                throw ValidationException::withMessages([
                    'patient_id' => 'برای این بیمار در مطب‌های مجاز شما سابقه مراجعه‌ای یافت نشد.',
                ]);
            }

            if (!empty($data['appointment_id'])) {
                $appointmentExists = $patient->appointments()
                    ->whereKey($data['appointment_id'])
                    ->where('doctor_id', $doctor->id)
                    ->whereIn('clinic_id', $accessibleClinicIds)
                    ->exists();

                if (!$appointmentExists) {
                    throw ValidationException::withMessages([
                        'appointment_id' => 'نوبت انتخاب‌شده متعلق به این بیمار و پزشک نیست.',
                    ]);
                }
            }

            $treatmentPlan = null;
            if (!empty($data['treatment_plan_id'])) {
                $treatmentPlan = PatientTreatmentPlan::query()
                    ->whereKey($data['treatment_plan_id'])
                    ->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient->id)
                    ->whereIn('clinic_id', $accessibleClinicIds)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->first();

                if (!$treatmentPlan) {
                    throw ValidationException::withMessages([
                        'treatment_plan_id' => 'طرح درمان انتخاب‌شده متعلق به این بیمار، پزشک یا مطب مجاز نیست.',
                    ]);
                }
            }

            $clinicId = $treatmentPlan?->clinic_id ?? (!empty($data['appointment_id'])
                ? $patient->appointments()->whereKey($data['appointment_id'])->value('clinic_id')
                : $patient->appointments()->where('doctor_id', $doctor->id)
                    ->whereIn('clinic_id', $accessibleClinicIds)
                    ->orderByDesc('appointment_date')->orderByDesc('id')->value('clinic_id'));

            if ($treatmentPlan && !empty($data['appointment_id'])) {
                $appointmentClinicId = $patient->appointments()->whereKey($data['appointment_id'])->value('clinic_id');
                if ((int) $appointmentClinicId !== (int) $treatmentPlan->clinic_id) {
                    throw ValidationException::withMessages([
                        'appointment_id' => 'نوبت و طرح درمان باید متعلق به یک مطب باشند.',
                    ]);
                }
            }

            if (!$clinicId || !in_array((int) $clinicId, $accessibleClinicIds, true)) {
                throw ValidationException::withMessages([
                    'patient_id' => 'مطب مجاز برای ثبت پیگیری بیمار مشخص نشد.',
                ]);
            }

            $template = null;
            if (!empty($data['template_id'])) {
                $template = PatientFollowUpTemplate::query()
                    ->whereKey($data['template_id'])
                    ->where('doctor_id', $doctor->id)
                    ->where('is_active', true)
                    ->firstOrFail();
            }

            $assigneeId = $data['assigned_to'] ?? null;
            if ($assigneeId !== null) {
                $secretary = $doctor->secretaries()
                    ->where('users.id', $assigneeId)
                    ->where('users.is_active', true)
                    ->first();

                $secretaryClinicIds = $secretary?->pivot?->clinic_ids ?? [];
                if (is_string($secretaryClinicIds)) {
                    $secretaryClinicIds = json_decode($secretaryClinicIds, true) ?: [];
                }

                if (!$secretary || !is_array($secretaryClinicIds) || !in_array((int) $clinicId, array_map('intval', $secretaryClinicIds), true)) {
                    throw ValidationException::withMessages([
                        'assigned_to' => 'مسئول انتخاب‌شده، منشی فعال و متصل به این پزشک نیست.',
                    ]);
                }
            }

            $planUuid = (string) Str::uuid();
            $steps = $data['steps'] ?? [[
                'title' => $template?->title ?? $data['title'],
                'days' => (int) ($template?->default_days ?? 0),
                'contact_method' => $template?->contact_method ?? ($data['contact_method'] ?? 'call'),
                'send_sms' => ((bool) ($data['send_sms'] ?? false) || (bool) ($template?->send_sms ?? false)) && (bool) ($data['sms_consent_confirmed'] ?? false),
                'sms_consent_confirmed' => (bool) ($data['sms_consent_confirmed'] ?? false),
            ]];

            $baseDate = Carbon::parse($data['due_at']);
            $tasks = collect();

            foreach ($steps as $step) {
                $tasks->push(PatientFollowUpTask::create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinicId,
                    'patient_id' => $patient->id,
                    'appointment_id' => $data['appointment_id'] ?? null,
                    'treatment_plan_id' => $treatmentPlan?->id,
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
                        && (bool) ($step['sms_consent_confirmed'] ?? false)
                        && (!$treatmentPlan || (
                            $treatmentPlan->follow_up_sms_enabled
                            && $treatmentPlan->follow_up_sms_consent_confirmed
                            && !$treatmentPlan->follow_up_sms_consent_revoked_at
                        )),
                    'sms_consent_confirmed' => (bool) ($step['sms_consent_confirmed'] ?? false),
                    'status' => 'pending',
                ]));
            }

            return $tasks;
        });
    }
}