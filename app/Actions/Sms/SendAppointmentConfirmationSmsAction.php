<?php

declare(strict_types=1);

namespace App\Actions\Sms;

use App\Contracts\Sms\SmsGateway;
use App\Models\Appointment;
use App\Models\SmsLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SendAppointmentConfirmationSmsAction
{
    public function __construct(
        private readonly SmsGateway $gateway
    ) {
    }

    public function handle(int $visitNumber): JsonResponse
    {
        $appointment = Appointment::query()
            ->with('clinic.city')
            ->find($visitNumber);

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'message' => 'نوبت موردنظر پیدا نشد.',
            ], 404);
        }

        if ($appointment->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'برای نوبت لغوشده امکان ارسال پیامک تأیید وجود ندارد.',
            ], 422);
        }

        $sms = $this->inputsBuilder->handle($appointment);
        $mobile = $sms['mobile'];

        if (!preg_match('/^09\d{9}$/', $mobile)) {
            return response()->json([
                'success' => false,
                'message' => 'شماره موبایل بیمار معتبر نیست.',
            ], 422);
        }

        $template = trim((string) config(
            'services.ghasedak.appointment_confirmation_template',
            'AppointmentConfirmationSMS'
        ));

        if ($template === '' || !config('services.ghasedak.api_key')) {
            Log::warning('Appointment confirmation SMS unavailable: provider or template not configured', [
                'appointment_id' => $visitNumber,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'سرویس ارسال پیامک در حال حاضر پیکربندی نشده است.',
            ], 503);
        }

        $cacheKey = 'appointment-confirmation-sms:' . $visitNumber;

        if (!Cache::add($cacheKey, true, now()->addMinutes(3))) {
            return response()->json([
                'success' => false,
                'message' => 'پیامک اخیراً ارسال شده است. لطفاً ۳ دقیقه صبر کنید.',
                'visit_number' => $visitNumber,
            ], 429);
        }

        $smsLog = null;

        try {
            $smsLog = SmsLog::create([
                'appointment_id' => $appointment->id,
                'type' => 'appointment_confirmation',
                'status' => 'pending',
                'provider' => 'ghasedak',
            ]);

            $this->gateway->sendTemplate(
                $mobile,
                $template,
                $sms['inputs'],
                'visit-' . $visitNumber
            );

            $smsLog->update([
                'status' => 'sent',
                'sent_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'پیامک تأیید نوبت با موفقیت ارسال شد.',
                'visit_number' => $visitNumber,
            ]);
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            if ($smsLog) {
                $smsLog->update([
                    'status' => 'failed',
                    'error_message' => 'provider_or_connection_error',
                ]);
            }

            Log::error('Appointment confirmation SMS failed', [
                'appointment_id' => $visitNumber,
                'exception' => get_class($exception),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خطا در ارسال پیامک. لطفاً مجدداً تلاش کنید.',
            ], 503);
        }
    }
}
