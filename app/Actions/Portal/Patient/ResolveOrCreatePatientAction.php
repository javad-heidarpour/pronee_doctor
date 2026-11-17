<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

final class ResolveOrCreatePatientAction
{
    /**
     * Resolve a patient inside one doctor's workspace.
     *
     * Callers create appointments inside a transaction. The doctor row lock
     * serializes patient resolution across public and portal bookings.
     */
    public function handle(
        Doctor $doctor,
        string $name,
        ?string $phone,
        ?string $nationalCode
    ): ?Patient {
        $phone = $this->normalize($phone);
        $nationalCode = $this->normalize($nationalCode);
        $name = trim($name);

        if ($phone === null && $nationalCode === null) {
            return null;
        }

        return DB::transaction(function () use ($doctor, $name, $phone, $nationalCode): Patient {
            Doctor::query()
                ->whereKey($doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            $query = Patient::query()->where('doctor_id', $doctor->id);

            if ($nationalCode !== null) {
                $query->where('national_code', $nationalCode);
            } else {
                // A phone-only identity must not silently merge into a
                // national-code identity that happens to share that phone.
                $query->whereNull('national_code')->where('phone', $phone);
            }

            $patient = $query->lockForUpdate()->first();

            if ($patient) {
                $patient->forceFill([
                    'name' => $name !== '' ? $name : $patient->name,
                    'phone' => $phone ?? $patient->phone,
                    'national_code' => $nationalCode ?? $patient->national_code,
                ])->save();

                return $patient;
            }

            return Patient::query()->create([
                'doctor_id' => $doctor->id,
                'name' => $name !== '' ? $name : 'بیمار بدون نام',
                'phone' => $phone,
                'national_code' => $nationalCode,
            ]);
        });
    }

    private function normalize(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
