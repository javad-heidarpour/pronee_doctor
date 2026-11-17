<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class RegisterDoctorAction
{
    public function handle(
        array $data,
        string $phone,
        string $registrationToken,
        string $temporaryPassword
    ): ?Doctor {
        $tokenKey = $this->registrationTokenKey($registrationToken);

        // Serialize registrations for the same phone as well as token consumption.
        $phoneLock = Cache::lock(
            'doctor-registration-phone:' . hash('sha256', $phone),
            120
        );

        if (! $phoneLock->get()) {
            return null;
        }

        $registrationLock = Cache::lock(
            'doctor-registration-consume:' . hash('sha256', $registrationToken),
            120
        );

        if (! $registrationLock->get()) {
            $phoneLock->release();

            return null;
        }

        try {
            $verifiedPhone = Cache::get($tokenKey);

            if (! $verifiedPhone || ! hash_equals((string) $verifiedPhone, $phone)) {
                return null;
            }

            if (User::query()->where('phone', $phone)->exists()) {
                Cache::forget($tokenKey);

                return null;
            }

            $doctor = DB::transaction(function () use ($data, $phone, $temporaryPassword): Doctor {
                $user = new User();

                $user->forceFill([
                    'name' => $data['name'],
                    'family' => $data['family'],
                    'username' => $phone,
                    'email' => $data['email'] ?? null,
                    'phone' => $phone,
                    'password' => $temporaryPassword,
                    'role' => 'doctor',
                    'is_active' => true,
                    'phone_verified_at' => now(),
                ]);

                $user->save();

                $doctor = new Doctor();

                $doctor->forceFill([
                    'user_id' => $user->id,
                    'city_id' => $data['city_id'],
                    'specialty_id' => $data['specialty_id'],
                    'name' => $data['name'],
                    'family' => $data['family'],
                    'slug' => $data['slug'],
                    'email' => $data['email'] ?? null,
                    'phone' => $phone,
                    'Medical_system_number' => $data['medical_system_number'] ?? null,
                    'is_active' => true,
                    'registration_status' => 'approved',
                    'approved_at' => now(),
                    'trial_started_at' => now(),
                    'site_expires_at' => now()->addDays(7),
                ]);

                $doctor->save();

                DB::table('doctor_user')->insert([
                    'doctor_id' => $doctor->id,
                    'user_id' => $user->id,
                    'permissions' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $doctor;
            });

            Cache::forget($tokenKey);

            return $doctor;
        } finally {
            $registrationLock->release();
            $phoneLock->release();
        }
    }

    private function registrationTokenKey(string $token): string
    {
        return 'doctor-registration-token:' . hash('sha256', $token);
    }
}
