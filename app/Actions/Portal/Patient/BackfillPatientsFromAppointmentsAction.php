<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

final class BackfillPatientsFromAppointmentsAction
{
    public function __construct(
        private readonly ResolveOrCreatePatientAction $resolvePatient
    ) {
    }

    /**
     * Link legacy appointments to doctor-scoped patient records.
     * Safe to rerun: appointments that already have patient_id are skipped.
     *
     * @return array{processed:int, linked:int, skipped:int}
     */
    public function handle(int $chunkSize = 200): array
    {
        $stats = ['processed' => 0, 'linked' => 0, 'skipped' => 0];

        Appointment::query()
            ->whereNull('patient_id')
            ->orderBy('id')
            ->chunkById(max(1, min($chunkSize, 1000)), function ($appointments) use (&$stats): void {
                foreach ($appointments as $appointment) {
                    $stats['processed']++;

                    DB::transaction(function () use ($appointment, &$stats): void {
                        // Keep the same lock order as booking: doctor, then appointment.
                        $doctor = Doctor::query()
                            ->whereKey($appointment->doctor_id)
                            ->lockForUpdate()
                            ->first();

                        if (!$doctor) {
                            $stats['skipped']++;
                            return;
                        }

                        $lockedAppointment = Appointment::query()
                            ->whereKey($appointment->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$lockedAppointment || $lockedAppointment->patient_id !== null) {
                            $stats['skipped']++;
                            return;
                        }

                        // Read identifiers only after locking the current appointment;
                        // the chunked model may contain stale identity values.
                        $phone = trim((string) $lockedAppointment->patient_phone);
                        $nationalCode = trim((string) $lockedAppointment->patient_national_code);

                        if ($phone === '' && $nationalCode === '') {
                            $stats['skipped']++;
                            return;
                        }

                        $patient = $this->resolvePatient->handle(
                            $doctor,
                            (string) $lockedAppointment->patient_name,
                            $phone !== '' ? $phone : null,
                            $nationalCode !== '' ? $nationalCode : null
                        );

                        if (!$patient) {
                            $stats['skipped']++;
                            return;
                        }

                        $lockedAppointment->forceFill(['patient_id' => $patient->id])->save();
                        $stats['linked']++;
                    });
                }
            });

        return $stats;
    }
}
