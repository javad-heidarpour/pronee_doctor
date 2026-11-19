<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DTOs\Auth\RegisterDoctorData;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class RegisterDoctorAction
{
    public function handle(
        RegisterDoctorData $data,
        string $registrationToken,
        string $temporaryPassword
    ): ?Doctor {
        $tokenKey = $this->registrationTokenKey($registrationToken);

        $phoneLock = Cache::lock(
            'doctor-registration-phone:' . hash('sha256', $data->phone),
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

            if (! $verifiedPhone || ! hash_equals((string) $verifiedPhone, $data->phone)) {
                return null;
            }

            if (User::query()->where('phone', $data->phone)->exists()) {
                Cache::forget($tokenKey);

                return null;
            }

            $doctor = DB::transaction(function () use ($data, $temporaryPassword): Doctor {
                $user = new User();

                $user->forceFill([
                    'name' => $data->name,
                    'family' => $data->family,
                    'username' => $data->phone,
                    'email' => $data->email,
                    'phone' => $data->phone,
                    'password' => $temporaryPassword,
                    'role' => 'doctor',
                    'is_active' => false,
                ]);

                $user->save();

                $doctor = new Doctor();

                $doctor->forceFill([
                    'user_id' => $user->id,
                    'city_id' => $data->cityId,
                    'specialty_id' => $data->specialtyId,
                    'name' => $data->name,
                    'family' => $data->family,
                    'slug' => $data->slug,
                    'email' => $data->email,
                    'phone' => $data->phone,
                    'Medical_system_number' => $data->medicalSystemNumber,
                    'is_active' => false,
                    // ثبت‌نام پیامکی فقط مالکیت شماره را ثابت می‌کند، نه صلاحیت حرفه‌ای را.
                    // تا بررسی مدیر، هم حساب ورود و هم پروفایل عمومی غیرفعال می‌مانند.
                    'registration_status' => 'pending',
                    'approved_at' => null,
                    'approved_by' => null,
                    'trial_started_at' => null,
                    'site_expires_at' => null,
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
