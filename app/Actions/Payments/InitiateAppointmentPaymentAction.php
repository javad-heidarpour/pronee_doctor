<?php

namespace App\Actions\Payments;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Services\Payments\SepPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class InitiateAppointmentPaymentAction
{
    /**
     * Return the existing gateway token when a payment is already initiated.
     * A short DB-backed claim prevents concurrent browser requests from
     * asking SEP for multiple tokens for the same payment record.
     */
    public function handle(
        Appointment $appointment,
        string $cellNumber,
        SepPaymentService $sep
    ): ?string {
        $claim = DB::transaction(function () use ($appointment) {
            $lockedAppointment = Appointment::query()
                ->whereKey($appointment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($lockedAppointment->status, ['pending', 'confirmed'], true)) {
                throw new RuntimeException('این نوبت امکان پرداخت ندارد.');
            }

            // A public reservation that has expired must never start a new
            // payment attempt, even if the scheduler has not processed it yet.
            if (
                $lockedAppointment->status === 'pending' &&
                $lockedAppointment->reservation_expires_at !== null &&
                $lockedAppointment->reservation_expires_at->isPast()
            ) {
                throw new RuntimeException(
                    'مهلت رزرو این نوبت به پایان رسیده است؛ لطفاً زمان دیگری انتخاب کنید.'
                );
            }

            $payments = AppointmentPayment::query()
                ->where('appointment_id', $lockedAppointment->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->get();

            if ($payments->contains(fn ($item) => $item->status === 'paid')) {
                return ['type' => 'paid'];
            }

            // A stale attempt marked for manual reconciliation must not
            // request another SEP token: the previous gateway result is unknown.
            if ($payments->contains(
                fn ($item) => $item->gateway_result_code === 'REVIEW_REQUIRED'
            )) {
                throw new RuntimeException(
                    'وضعیت پرداخت قبلی نیازمند بررسی است؛ لطفاً برای پیگیری با مطب تماس بگیرید.'
                );
            }

            $amount = (int) $lockedAppointment->fee_rial;

            if ($amount <= 0) {
                throw new RuntimeException('مبلغ ویزیت معتبر نیست.');
            }

            $payment = $payments->first(
                fn ($item) => in_array($item->status, ['pending', 'initiated'], true)
            );

            if ($payment) {
                if ((int) $payment->amount_rial !== $amount) {
                    throw new RuntimeException(
                        'مبلغ پرداخت قبلی با مبلغ فعلی نوبت مطابقت ندارد.'
                    );
                }

                if ($payment->gateway_name !== 'sep') {
                    throw new RuntimeException('درگاه پرداخت معتبر نیست.');
                }

                $existingToken = trim((string) $payment->gateway_token);

                if ($payment->status === 'initiated' && $existingToken !== '') {
                    return ['type' => 'token', 'token' => $existingToken];
                }

                // Another request may currently be obtaining a token from SEP.
                if (
                    $payment->status === 'initiated' &&
                    $payment->updated_at &&
                    $payment->updated_at->gt(now()->subMinutes(2))
                ) {
                    throw new RuntimeException(
                        'درخواست پرداخت در حال آماده‌سازی است؛ لطفاً یک دقیقه دیگر دوباره تلاش کنید.'
                    );
                }

                $payment->status = 'initiated';
                $payment->gateway_token = null;
                $payment->save();

                return ['type' => 'claim', 'payment_id' => $payment->id];
            }

            $payment = AppointmentPayment::query()->create([
                'appointment_id' => $lockedAppointment->id,
                'amount_rial' => $amount,
                'status' => 'initiated',
                'payment_uuid' => (string) Str::uuid(),
                'gateway_name' => 'sep',
            ]);

            return ['type' => 'claim', 'payment_id' => $payment->id];
        });

        if ($claim['type'] === 'paid') {
            return null;
        }

        if ($claim['type'] === 'token') {
            return $claim['token'];
        }

        $payment = AppointmentPayment::query()->findOrFail($claim['payment_id']);

        Log::info('SEP TOKEN REQUEST CLAIMED', [
            'payment_id' => $payment->id,
            'appointment_id' => $appointment->id,
            'amount_rial' => $payment->amount_rial,
        ]);

        try {
            $callbackUrl = trim((string) config('services.sep.callback_url', ''));

            if ($callbackUrl === '') {
                $callbackUrl = route('payment.callback', [], true);
            }

            $result = $sep->requestToken(
                (int) $payment->amount_rial,
                (string) $payment->payment_uuid,
                $callbackUrl,
                $cellNumber
            );

            if (!is_array($result)) {
                throw new RuntimeException('پاسخ درگاه SEP در قالب معتبر دریافت نشد.');
            }

            $token = trim((string) ($result['token'] ?? ''));

            if ($token === '') {
                throw new RuntimeException('توکن پرداخت از درگاه دریافت نشد.');
            }

            $saved = DB::transaction(function () use ($payment, $token) {
                // Keep lock order consistent: appointment before payment.
                $lockedAppointment = Appointment::query()
                    ->whereKey($payment->appointment_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedPayment = AppointmentPayment::query()
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $lockedPayment->status === 'paid' ||
                    !in_array($lockedAppointment->status, ['pending', 'confirmed'], true)
                ) {
                    return false;
                }

                if ($lockedPayment->status !== 'initiated') {
                    throw new RuntimeException(
                        'وضعیت پرداخت هنگام ذخیره توکن تغییر کرده است.'
                    );
                }

                $lockedPayment->gateway_token = $token;
                $lockedPayment->save();

                return true;
            });

            if (!$saved) {
                throw new RuntimeException('این پرداخت هم‌زمان تکمیل شده است.');
            }

            Log::info('SEP PAYMENT TOKEN SAVED', [
                'payment_id' => $payment->id,
                'appointment_id' => $appointment->id,
            ]);

            return $token;
        } catch (Throwable $e) {
            DB::transaction(function () use ($payment) {
                // Lock the appointment first, matching every other payment
                // lifecycle transaction and preventing lock-order inversions.
                Appointment::query()
                    ->whereKey($payment->appointment_id)
                    ->lockForUpdate()
                    ->first();

                $lockedPayment = AppointmentPayment::query()
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->first();

                if (
                    $lockedPayment &&
                    $lockedPayment->status === 'initiated' &&
                    empty($lockedPayment->gateway_token)
                ) {
                    $lockedPayment->status = 'pending';
                    $lockedPayment->save();
                }
            });

            throw $e;
        }
    }
}
