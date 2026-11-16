<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Auth\BuildDoctorRegistrationFormAction;
use App\Actions\Auth\RegisterDoctorAction;
use App\Actions\Auth\SendDoctorRegistrationOtpAction;
use App\Actions\Auth\VerifyDoctorRegistrationOtpAction;
use App\Actions\Auth\SendDoctorRegistrationPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendDoctorRegistrationOtpRequest;
use App\Http\Requests\Auth\StoreDoctorRegistrationRequest;
use App\Http\Requests\Auth\VerifyDoctorRegistrationOtpRequest;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Throwable;

class DoctorRegistrationController extends Controller
{
    /**
     * نمایش فرم ثبت‌نام
     */
    public function create(BuildDoctorRegistrationFormAction $action)
    {
        return Inertia::render(
            'Auth/DoctorRegister',
            $action->handle()
        );
    }

    /**
     * ارسال کد تأیید موبایل
     */
    public function sendOtp(
        SendDoctorRegistrationOtpRequest $request,
        SendDoctorRegistrationOtpAction $action
    ) {
        $phone = $this->normalizePhone($request->validated('phone'));

        try {
            $action->handle($phone);

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
    public function verifyOtp(
        VerifyDoctorRegistrationOtpRequest $request,
        VerifyDoctorRegistrationOtpAction $action
    ) {
        $phone = $this->normalizePhone($request->validated('phone'));

        try {
            $result = $action->handle($phone, $request->validated('code'));

            if ($result['status'] === 'already_registered') {
                return response()->json([
                    'success' => false,
                    'message' => 'این شماره موبایل قبلاً ثبت شده است.',
                ], 422);
            }

            if ($result['status'] === 'invalid_code') {
                return response()->json([
                    'success' => false,
                    'message' => 'کد تأیید اشتباه یا منقضی شده است.',
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'شماره موبایل با موفقیت تأیید شد.',
                'registration_token' => $result['registration_token'],
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
