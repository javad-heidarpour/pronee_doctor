<?php

declare(strict_types=1);

namespace App\Services\HealthInterop;

use App\Models\Appointment;
use App\Models\HealthExchangeMessage;
use App\Models\PatientEncounter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class HealthExchangeOutbox
{
    public function enqueueAppointment(Appointment $appointment, string $destination = 'sepas'): HealthExchangeMessage
    {
        return $this->enqueue($appointment, null, $destination, 'appointment.booked', [
            'appointment_id' => $appointment->id,
            'scheduled_at' => $appointment->appointment_date?->format('Y-m-d').' '.$appointment->appointment_time,
            'clinic_id' => $appointment->clinic_id,
            'provider_id' => $appointment->doctor_id,
            'patient' => $this->patientIdentity($appointment->patient),
        ]);
    }

    public function enqueueEncounter(PatientEncounter $encounter, string $destination = 'sepas'): HealthExchangeMessage
    {
        abort_unless($encounter->status === PatientEncounter::STATUS_SIGNED, 409, 'فقط ویزیت نهایی‌شده قابل ارسال است.');
        $encounter->loadMissing(['patient', 'prescriptions']);

        return $this->enqueue($encounter->appointment, $encounter, $destination, 'encounter.signed', [
            'encounter_id' => $encounter->id,
            'appointment_id' => $encounter->appointment_id,
            'provider_id' => $encounter->doctor_id,
            'clinic_id' => $encounter->clinic_id,
            'signed_at' => $encounter->signed_at?->toIso8601String(),
            'diagnosis' => $encounter->diagnosis,
            'diagnosis_code' => $encounter->diagnosis_code,
            'assessment' => $encounter->assessment,
            'plan' => $encounter->plan,
            'patient' => $this->patientIdentity($encounter->patient),
            'prescriptions' => $encounter->prescriptions->map(fn ($item) => [
                'medication_name' => $item->medication_name,
                'strength' => $item->strength,
                'dosage' => $item->dosage,
                'route' => $item->route,
                'frequency' => $item->frequency,
                'duration_days' => $item->duration_days,
                'quantity' => $item->quantity,
                'instructions' => $item->instructions,
            ])->all(),
        ]);
    }

    private function enqueueAppointmentRecord(Appointment $appointment): array
    {
        return [
            'appointment_id' => $appointment->id,
            'scheduled_at' => $appointment->appointment_date?->format('Y-m-d').' '.$appointment->appointment_time,
            'clinic_id' => $appointment->clinic_id,
            'provider_id' => $appointment->doctor_id,
            'patient' => $this->patientIdentity($appointment->patient),
        ];
    }

    private function enqueue(Appointment $appointment, ?PatientEncounter $encounter, string $destination, string $event, array $payload): HealthExchangeMessage
    {
        $key = hash('sha256', implode(':', [$destination, $event, $encounter?->id ?? $appointment->id]));
        return DB::transaction(function () use ($appointment, $encounter, $destination, $event, $payload, $key) {
            return HealthExchangeMessage::query()->firstOrCreate(
                ['idempotency_key' => $key],
                [
                    'doctor_id' => $appointment->doctor_id,
                    'patient_id' => $appointment->patient_id,
                    'appointment_id' => $appointment->id,
                    'encounter_id' => $encounter?->id,
                    'destination' => $destination,
                    'event_type' => $event,
                    'status' => HealthExchangeMessage::PENDING,
                    'payload' => $payload,
                    'attempts' => 0,
                    'available_at' => now(),
                ]
            );
        });
    }

    private function patientIdentity($patient): array
    {
        return [
            'national_code' => $patient?->national_code,
            'name' => $patient?->name,
            'phone' => $patient?->phone,
        ];
    }
}
