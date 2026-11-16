<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendDoctorRegistrationOtpRequest;
use App\Http\Requests\Auth\StoreDoctorRegistrationRequest;
use App\Http\Requests\Auth\VerifyDoctorRegistrationOtpRequest;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Specialty;
use App\Models\User;
use App\Services\LoginOtp;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Throwable;

class DoctorRegistrationController extends Controller
{
    public function __construct(
        private LoginOtp $loginOtp
    ) {
    }

    /**
     * نمایش فرم ثبت‌نام
     */
    public function create()
    {
        $specialties = Specialty::where('is_active', 1)
            ->orderBy('sort_order')
            ->get(['id', 'name']);

        $cities = City::where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'province']);

        // dd($specialties, $cities);
        return Inertia::render('Auth/DoctorRegister', [
            'specialties' => $specialties,

            'cities' => $cities,
        ]);
    }

    /**
     * ارسال کد تأیید موبایل
     */
    public function sendOtp(SendDoctorRegistrationOtpRequest $request)
    {
        $phone = $this->normalizePhone($request->validated('phone'));

        try {
            $this->loginOtp->send($phone);

            return response()->json([
                'success' => true,
                'message' => 'کد تأیید برای شما ارسال شد.',
            ]);
        } catch (Throwable $e) {
            Log::warning('Doctor registration OTP failed', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * تأیید کد موبایل و صدور توکن ثبت‌نام
     */
    public function verifyOtp(VerifyDoctorRegistrationOtpRequest $request)
    {
        $phone = $this->normalizePhone($request->validated('phone'));

        if (User::where('phone', $phone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'این شماره موبایل قبلاً ثبت شده است.',
            ], 422);
        }

        try {
            if (! $this->loginOtp->verify($phone, $request->validated('code'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'کد تأیید اشتباه یا منقضی شده است.',
                ], 422);
            }

            $token = Str::random(64);

            Cache::put(
                $this->registrationTokenKey($token),
                $phone,
                now()->addMinutes(10)
            );

            return response()->json([
                'success' => true,
                'message' => 'شماره موبایل با موفقیت تأیید شد.',
                'registration_token' => $token,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ثبت نهایی پزشک و ایجاد سایت
     */
    public function store(StoreDoctorRegistrationRequest $request)
    {
        $phone = $this->normalizePhone($request->phone);

        // اعتبارسنجی توکن و تطبیق آن با شماره تأییدشده
        $tokenKey = $this->registrationTokenKey(
            $request->registration_token
        );

        $verifiedPhone = Cache::get($tokenKey);

        if (!$verifiedPhone || $verifiedPhone !== $phone) {
            return response()->json([
                'success' => false,
                'message' => 'تأیید شماره موبایل معتبر نیست. دوباره کد تأیید بگیرید.',
            ], 422);
        }

        if (User::where('phone', $phone)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'این شماره موبایل قبلاً ثبت شده است.',
            ], 422);
        }

        $temporaryPassword = Str::random(12);

        try {
            $doctor = DB::transaction(function () use ($request, $phone, $temporaryPassword) {
                // ایجاد حساب کاربری
                $user = new User();

                $user->forceFill([
                    'name' => $request->name,
                    'family' => $request->family,
                    'username' => $phone,
                    'email' => $request->email ?: null,
                    'phone' => $phone,
                    'password' => Hash::make($temporaryPassword),
                    'role' => 'doctor',
                    'is_active' => 1,
                    'phone_verified_at' => now(),
                ]);

                $user->save();

                // ایجاد پروفایل پزشک و سایت
                $doctor = new Doctor();

                $doctor->forceFill([
                    'user_id' => $user->id,
                    'city_id' => $request->city_id,
                    'specialty_id' => $request->specialty_id,
                    'name' => $request->name,
                    'family' => $request->family,
                    'slug' => $request->slug,
                    'email' => $request->email ?: null,
                    'phone' => $phone,
                    'Medical_system_number' =>
                        $request->medical_system_number,
                    'role' => 'doctor',
                    'is_active' => 1,
                    'registration_status' => 'approved',
                    'approved_at' => now(),
                    'trial_started_at' => now(),
                    'site_expires_at' => now()->addDays(7),
                ]);

                $doctor->save();

                // اتصال حساب کاربری به پروفایل پزشک
                DB::table('doctor_user')->insert([
                    'doctor_id' => $doctor->id,
                    'user_id' => $user->id,
                    'permissions' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return $doctor;
            });

            // توکن ثبت‌نام پس از ثبت موفق یک‌بار مصرف است
            Cache::forget($tokenKey);

        } catch (Throwable $e) {
            Log::error('Doctor registration failed', [
                'phone' => $phone,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'ثبت‌نام انجام نشد. لطفاً اطلاعات را بررسی کنید.',
            ], 500);
        }

        // ارسال رمز عبور پس از ثبت موفق اطلاعات
        $smsSent = false;

        try {
            $this->loginOtp->sendTemplate(
                $phone,
                'DoctorPassword',
                [
                    [
                        'param' => 'param1',
                        'value' => $temporaryPassword,
                    ],
                ],
                'doctor-password-' . $doctor->id
            );

            $smsSent = true;

        } catch (Throwable $e) {
            Log::error('Doctor password SMS failed', [
                'doctor_id' => $doctor->id,
                'message' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $smsSent
                ? 'ثبت‌نام با موفقیت انجام شد. رمز عبور برای شما پیامک شد.'
                : 'حساب شما ایجاد شد، اما ارسال پیامک رمز عبور ناموفق بود. از بازیابی رمز عبور استفاده کنید.',
            'sms_sent' => $smsSent,
            'doctor_url' => url('/' . $doctor->slug),
            'redirect_url' => url('/portal/login'),
        ]);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D/', '', $phone);
    }

    private function registrationTokenKey(string $token): string
    {
        return 'doctor-registration-token:' . hash('sha256', $token);
    }
}
