<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Models\AppointmentPayment;
use App\Models\AppointmentPaymentRefund;
use App\Services\Finance\ClinicAccountingService;
use App\Services\Payments\SepPaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ReverseAppointmentPaymentAction
{
    public function handle(
        AppointmentPayment $payment,
        int $actorUserId,
        string $reason,
        SepPaymentService $sep,
        ClinicAccountingService $accounting
    ): array {
        $reason = trim($reason);
        if ($actorUserId < 1 || mb_strlen($reason) < 8 || mb_strlen($reason) > 1000) {
            throw new RuntimeException('ثبت درخواست برگشت وجه با اطلاعات معتبر الزامی است.');
        }

        $refund = DB::transaction(function () use ($payment, $actorUserId, $reason): AppointmentPaymentRefund {
            $lockedPayment = AppointmentPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($lockedPayment->status !== 'paid' || !$lockedPayment->paid_at) {
                throw new RuntimeException('فقط پرداخت تأییدشده و دارای زمان پرداخت قابل برگشت است.');
            }
            if (strtolower((string) $lockedPayment->gateway_name) !== 'sep') {
                throw new RuntimeException('برگشت خودکار فقط برای تراکنش‌های سامان‌کیش فعال است.');
            }
            $refNum = trim((string) $lockedPayment->ref_num);
            $terminalId = trim((string) ($lockedPayment->terminal_id ?: config('services.sep.terminal_id', '')));
            $configuredTerminal = trim((string) config('services.sep.terminal_id', ''));
            if ($refNum === '' || $terminalId === '' || $configuredTerminal === '' || !hash_equals($configuredTerminal, $terminalId)) {
                throw new RuntimeException('شناسه مرجع یا ترمینال پرداخت با تنظیمات پذیرنده تطبیق ندارد.');
            }

            $existing = AppointmentPaymentRefund::query()
                ->where('appointment_payment_id', $lockedPayment->id)
                ->lockForUpdate()->first();
            if ($existing) {
                throw new RuntimeException(match ($existing->status) {
                    'reversed' => 'این پرداخت قبلاً برگشت داده شده است.',
                    'processing', 'unknown' => 'نتیجه برگشت قبلی نامشخص است؛ ابتدا باید از گزارش پذیرنده تطبیق انجام شود.',
                    default => 'برای این پرداخت سابقه برگشت وجود دارد و ارسال مجدد خودکار مجاز نیست.',
                });
            }

            return AppointmentPaymentRefund::query()->create([
                'appointment_payment_id' => $lockedPayment->id,
                'requested_by_user_id' => $actorUserId,
                'amount_rial' => (int) $lockedPayment->amount_rial,
                'reason' => $reason,
                'status' => 'processing',
                'gateway_ref_num' => $refNum,
                'gateway_terminal_id' => $terminalId,
                'idempotency_key' => hash('sha256', 'sep-payment-refund:' . $lockedPayment->id),
                'requested_at' => now(),
            ]);
        }, 3);

        try {
            $response = $sep->reverseTransaction($refund->gateway_ref_num);
        } catch (Throwable $exception) {
            $this->markUnknown($refund->id, $actorUserId, 'نتیجه ارتباط با سامان‌کیش قطعی نیست؛ استعلام پذیرنده لازم است.');
            report($exception);

            return ['status' => 'unknown', 'refund_id' => $refund->id];
        }

        $resultCode = $response['ResultCode'] ?? null;
        $message = (string) ($response['ResultDescription'] ?? $response['Message'] ?? 'پاسخ برگشت تراکنش دریافت شد.');
        if (!is_numeric($resultCode) || (int) $resultCode !== 0) {
            AppointmentPaymentRefund::query()->whereKey($refund->id)->update([
                'status' => 'rejected',
                'processed_by_user_id' => $actorUserId,
                'gateway_result_code' => is_scalar($resultCode) ? (string) $resultCode : null,
                'gateway_result_message' => Str::limit($message, 1000),
                'gateway_response' => $response,
                'processed_at' => now(),
                'updated_at' => now(),
            ]);

            return ['status' => 'rejected', 'refund_id' => $refund->id];
        }

        try {
            DB::transaction(function () use ($refund, $payment, $actorUserId, $response, $message, $accounting): void {
                $lockedRefund = AppointmentPaymentRefund::query()->whereKey($refund->id)->lockForUpdate()->firstOrFail();
                $lockedPayment = AppointmentPayment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
                if ($lockedRefund->status !== 'processing' || $lockedPayment->status !== 'paid') {
                    throw new RuntimeException('وضعیت محلی پرداخت با نتیجه برگشت درگاه سازگار نیست.');
                }

                $lockedRefund->update([
                    'status' => 'reversed',
                    'processed_by_user_id' => $actorUserId,
                    'gateway_result_code' => '0',
                    'gateway_result_message' => Str::limit($message, 1000),
                    'gateway_response' => $response,
                    'processed_at' => now(),
                ]);
                $lockedPayment->update([
                    'status' => 'refunded',
                    'gateway_result_code' => 'reversed',
                    'gateway_result_message' => 'برگشت وجه توسط سامان‌کیش تأیید شد.',
                ]);

                $appointment = $lockedPayment->appointment()->firstOrFail();
                $organizationId = Schema::hasTable('clinic_organization_clinics')
                    ? DB::table('clinic_organization_clinics')->where('clinic_id', $appointment->clinic_id)
                        ->where('status', 'active')->value('organization_id')
                    : null;

                $accounting->reverseAppointmentPayment(
                    $organizationId ? (int) $organizationId : null,
                    (int) $appointment->clinic_id,
                    (int) $appointment->doctor_id,
                    $actorUserId,
                    (int) $lockedPayment->id,
                    (int) $lockedRefund->id,
                    (int) $lockedRefund->amount_rial,
                    'برگشت وجه پرداخت نوبت #' . $appointment->id,
                    now()->toDateTimeString()
                );
            }, 3);
        } catch (Throwable $exception) {
            $this->markUnknown($refund->id, $actorUserId, 'درگاه برگشت را تأیید کرده اما ثبت داخلی کامل نشده است؛ تطبیق دستی لازم است.');
            report($exception);

            return ['status' => 'unknown', 'refund_id' => $refund->id];
        }

        return ['status' => 'reversed', 'refund_id' => $refund->id];
    }

    private function markUnknown(int $refundId, int $actorUserId, string $message): void
    {
        AppointmentPaymentRefund::query()->whereKey($refundId)->whereIn('status', ['processing', 'reversed'])->update([
            'status' => 'unknown',
            'processed_by_user_id' => $actorUserId,
            'gateway_result_message' => $message,
            'processed_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
