<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\RegisterDoctorAction;
use App\Actions\Auth\SendDoctorRegistrationPasswordAction;
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
    public function store(
        StoreDoctorRegistrationRequest $request,
        RegisterDoctorAction $registerDoctor,
        SendDoctorRegistrationPasswordAction $sendPassword
    ) {
        $data = $request->validated();
        $phone = $this->normalizePhone($data['phone']);
        $temporaryPassword = Str::random(12);

        try {
            $doctor = $registerDoctor->handle(
                $data,
                $phone,
                $data['registration_token'],
                $temporaryPassword
            );

            if (! $doctor) {
                return response()->json([
                    'success' => false,
                    'message' => 'تأیید شماره موبایل معتبر نیست یا این شماره قبلاً ثبت شده است. دوباره کد تأیید بگیرید.',
                ], 422);
            }
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
        $smsSent = $sendPassword->handle(
            $phone,
            $temporaryPassword,
            (int) $doctor->id
        );

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
