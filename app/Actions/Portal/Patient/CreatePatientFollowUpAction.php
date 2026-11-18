<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Models\PatientFollowUpTemplate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class CreatePatientFollowUpAction
{
    public function handle(Doctor $doctor, User $actor, array $data): PatientFollowUpTask
    {
        return DB::transaction(function () use ($doctor, $actor, $data): PatientFollowUpTask {
            $patient = Patient::query()
                ->whereKey($data['patient_id'])
                ->where('doctor_id', $doctor->id)
                ->firstOrFail();

            if (!empty($data['appointment_id'])) {
                $appointmentExists = $patient->appointments()
                    ->whereKey($data['appointment_id'])
                    ->where('doctor_id', $doctor->id)
                    ->exists();

                if (!$appointmentExists) {
                    throw ValidationException::withMessages([
                        'appointment_id' => 'نوبت انتخاب‌شده متعلق به این بیمار و پزشک نیست.',
                    ]);
                }
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
                $isAssignedSecretary = $doctor->secretaries()
                    ->where('users.id', $assigneeId)
                    ->where('users.is_active', true)
                    ->exists();

                if (!$isAssignedSecretary) {
                    throw ValidationException::withMessages([
                        'assigned_to' => 'مسئول انتخاب‌شده، منشی فعال و متصل به این پزشک نیست.',
                    ]);
                }
            }

            return PatientFollowUpTask::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'appointment_id' => $data['appointment_id'] ?? null,
                'template_id' => $template?->id,
                'assigned_to' => $assigneeId,
                'created_by' => $actor->id,
                'plan_uuid' => (string) Str::uuid(),
                'title' => $template?->title ?? $data['title'],
                'description' => $template?->description ?? ($data['description'] ?? null),
                'due_at' => $data['due_at'],
                'priority' => $data['priority'] ?? 'normal',
                'contact_method' => $template?->contact_method ?? ($data['contact_method'] ?? 'call'),
                'status' => 'pending',
            ]);
        });
    }
}