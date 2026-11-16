<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Services\Payments\SepPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class InitiateAppointmentPaymentAction
{
    /**
     * ایجاد/بازیابی رکورد پرداخت و دریافت توکن جدید SEP.
     *
     * مقدار null یعنی این نوبت قبلاً پرداخت شده است.
     */
    public function handle(
        Appointment $appointment,
        string $cellNumber,
        SepPaymentService $sep
    ): ?string {
        $payment = DB::transaction(function () use ($appointment) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedAppointment->status, ['pending', 'confirmed'], true)) {
                throw new RuntimeException('این نوبت امکان پرداخت ندارد.');
            }

            $alreadyPaid = AppointmentPayment::query()
                ->where('appointment_id', $lockedAppointment->id)
                ->where('status', 'paid')
                ->exists();

            if ($alreadyPaid) {
                return null;
            }

            $amount = (int) $lockedAppointment->fee_rial;

            if ($amount <= 0) {
                throw new RuntimeException('مبلغ ویزیت معتبر نیست.');
            }

            $payment = AppointmentPayment::query()
                ->where('appointment_id', $lockedAppointment->id)
                ->whereIn('status', ['pending', 'initiated'])
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if ($payment) {
                if ((int) $payment->amount_rial !== $amount) {
                    throw new RuntimeException(
                        'مبلغ پرداخت قبلی با مبلغ فعلی نوبت مطابقت ندارد.'
                    );
                }

                if ($payment->gateway_name !== 'sep') {
                    throw new RuntimeException('درگاه پرداخت معتبر نیست.');
                }

                return $payment;
            }

            return AppointmentPayment::query()->create([
                'appointment_id' => $lockedAppointment->id,
                'amount_rial' => $amount,
                'status' => 'pending',
                'payment_uuid' => (string) Str::uuid(),
                'gateway_name' => 'sep',
            ]);
        });

        if ($payment === null) {
            return null;
        }

        Log::info('PAYMENT RECORD READY', [
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'has_token' => !empty($payment->gateway_token),
            'amount_rial' => $payment->amount_rial,
        ]);

        $token = trim((string) $payment->gateway_token);

        if ($payment->status === 'initiated' && $token !== '') {
            Log::warning('SEP EXISTING TOKEN REUSED', [
                'payment_id' => $payment->id,
                'token_length' => strlen($token),
            ]);

            // توکن قبلی استفاده نمی‌شود؛ توکن جدید از SEP درخواست می‌شود.
            $payment->gateway_token = null;
            $payment->status = 'pending';
            $payment->save();

            $token = '';
        }

        $callbackUrl = 'https://doctorvafaei.com/payment/callback';

        Log::info('SEP TOKEN REQUEST STARTED', [
            'payment_id' => $payment->id,
            'amount_rial' => $payment->amount_rial,
        ]);

        $result = $sep->requestToken(
            (int) $payment->amount_rial,
            (string) $payment->payment_uuid,
            $callbackUrl,
            $cellNumber
        );

        Log::info('SEP TOKEN RESPONSE RECEIVED', [
            'payment_id' => $payment->id,
            'result_type' => get_debug_type($result),
            'result_keys' => is_array($result) ? array_keys($result) : [],
            'has_token' => is_array($result) && !empty($result['token']),
        ]);

        if (!is_array($result)) {
            throw new RuntimeException('پاسخ درگاه SEP در قالب معتبر دریافت نشد.');
        }

        $token = trim((string) ($result['token'] ?? ''));

        if ($token === '') {
            throw new RuntimeException('توکن پرداخت از درگاه دریافت نشد.');
        }

        $payment->gateway_token = $token;
        $payment->status = 'initiated';
        $payment->save();

        Log::info('SEP PAYMENT INITIALIZED', [
            'payment_id' => $payment->id,
            'appointment_id' => $appointment->id,
            'amount_rial' => $payment->amount_rial,
        ]);

        return $token;
    }
}