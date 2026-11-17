<?php

namespace App\Actions\Payments;

use App\Models\AppointmentPayment;
use App\Services\Payments\SepPaymentService;
use RuntimeException;

class ReconcileKnownSepPaymentAction
{
    public function __construct(
        private readonly VerifyAndSettleSepPaymentAction $verifyAndSettle,
        private readonly CancelFailedAppointmentAction $cancelFailedAppointment,
        private readonly SepPaymentService $sep,
    ) {
    }

    /**
     * Re-query SEP only when a callback reference is already known.
     * A missing RefNum cannot safely be guessed from a payment UUID.
     *
     * @return array{status:string}
     */
    public function handle(AppointmentPayment $payment): array
    {
        $payment->refresh();

        if ($payment->gateway_name !== 'sep') {
            throw new RuntimeException('این عملیات فقط برای پرداخت‌های سامان‌کیش مجاز است.');
        }

        if (! in_array($payment->status, ['pending', 'initiated'], true)) {
            throw new RuntimeException('وضعیت این پرداخت برای استعلام مجدد مناسب نیست.');
        }

        $refNum = trim((string) $payment->ref_num);

        if ($refNum === '') {
            throw new RuntimeException(
                'شناسه پیگیری درگاه ثبت نشده است؛ این پرداخت باید از گزارش پذیرنده سامان‌کیش پیگیری شود.'
            );
        }

        $appointment = $payment->appointment;

        if (! $appointment) {
            throw new RuntimeException('نوبت مرتبط با پرداخت پیدا نشد.');
        }

        $terminalId = trim((string) config('services.sep.terminal_id', ''));

        if ($terminalId === '') {
            throw new RuntimeException('شناسه پذیرنده سامان‌کیش پیکربندی نشده است.');
        }

        return $this->verifyAndSettle->handle(
            $payment,
            $appointment,
            $refNum,
            $terminalId,
            $this->sep,
            $this->cancelFailedAppointment,
        );
    }
}
