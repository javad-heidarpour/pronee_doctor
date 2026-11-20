<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Payments\ListPaymentsRequiringReviewAction;
use App\Actions\Payments\ReverseAppointmentPaymentAction;
use App\Services\Payments\SepPaymentService;
use App\Services\Finance\ClinicAccountingService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Actions\Payments\ReconcileKnownSepPaymentAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\AppointmentPayment;
use App\Http\Controllers\Controller;
use RuntimeException;
use App\Http\Requests\Portal\ListPaymentReviewRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReviewController extends Controller
{
    /**
     * صف تطبیق درگاه و بررسی پرداخت‌های مشکوک فقط برای مدیر سامانه است.
     */

    /**
     * Reconcile a known SEP reference against the provider before settlement.
     */
    public function reconcile(
        AppointmentPayment $payment,
        ReconcileKnownSepPaymentAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);

        try {
            $result = $action->handle($payment);
        } catch (RuntimeException) {
            $audit->handle('payments.reconciliation.rejected', $payment, [
                'reconciliation_status' => 'rejected',
            ]);

            return back()->with('error', 'استعلام پرداخت انجام نشد یا اطلاعات کافی برای بررسی وجود ندارد. وضعیت پرداخت بدون تغییر باقی ماند.');
        }

        $audit->handle('payments.reconciliation.checked', $payment, [
            'reconciliation_status' => $result['status'] ?? 'unknown',
        ]);

        return back()->with(
            ($result['status'] ?? null) === 'success'
                ? 'success'
                : 'info',
            match ($result['status'] ?? null) {
                'success' => 'تراکنش با استعلام معتبر سامان‌کیش تأیید و ثبت شد.',
                'already_paid' => 'این پرداخت قبلاً تأیید شده است.',
                'failed' => 'سامان‌کیش تراکنش را تأیید نکرد؛ وضعیت نوبت طبق قواعد پرداخت به‌روزرسانی شد.',
                'waiting' => 'نتیجه استعلام قطعی نبود؛ نوبت همچنان محفوظ می‌ماند.',
                'verification_mismatch', 'amount_mismatch' => 'مغایرت اطلاعات تراکنش شناسایی شد؛ پرداخت تغییر نکرد و نیازمند بررسی پذیرنده است.',
                default => 'وضعیت پرداخت قابل تغییر نبود؛ بررسی دستی لازم است.',
            }
        );
    }

    public function refunds(): Response
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);

        return Inertia::render('Portal/Payments/Refunds', [
            'payments' => AppointmentPayment::query()
                ->with([
                    'appointment:id,doctor_id,clinic_id,appointment_date,appointment_time',
                    'refund:id,appointment_payment_id,amount_rial,reason,status,gateway_result_code,gateway_result_message,requested_at,processed_at',
                ])
                ->whereIn('status', ['paid', 'refunded'])
                ->whereNotNull('ref_num')
                ->latest('id')
                ->paginate(20)
                ->withQueryString(),
        ]);
    }

    public function refund(
        Request $request,
        AppointmentPayment $payment,
        ReverseAppointmentPaymentAction $action,
        SepPaymentService $sep,
        ClinicAccountingService $accounting,
        RecordSecurityAuditEventAction $audit
    ): RedirectResponse {
        $user = Auth::guard('web')->user();
        abort_unless($user?->role === 'admin', 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        try {
            $result = $action->handle($payment, (int) $user->id, $data['reason'], $sep, $accounting);
        } catch (RuntimeException $exception) {
            $audit->handle('payments.refund.rejected', $payment, [
                'refund_status' => 'rejected',
                'reason_length' => mb_strlen($data['reason']),
            ]);

            return back()->with('error', $exception->getMessage());
        }

        $audit->handle('payments.refund.' . $result['status'], $payment, [
            'refund_id' => $result['refund_id'] ?? null,
            'refund_status' => $result['status'],
        ]);

        return back()->with(
            match ($result['status']) {
                'reversed' => 'success',
                'rejected' => 'error',
                default => 'warning',
            },
            match ($result['status']) {
                'reversed' => 'برگشت وجه توسط سامان‌کیش تأیید و در سامانه ثبت شد.',
                'rejected' => 'درخواست برگشت وجه توسط درگاه رد شد؛ وضعیت پرداخت تغییر نکرد.',
                default => 'نتیجه برگشت وجه نامشخص است؛ از ارسال مجدد خودداری و با گزارش پذیرنده تطبیق دهید.',
            }
        );
    }

    public function index(
        ListPaymentReviewRequest $request,
        ListPaymentsRequiringReviewAction $action
    ): Response {
        // فهرست پرداخت‌های نیازمند بررسی، اطلاعات مالی چند پزشک را تجمیع می‌کند؛
        // پزشک و منشی نباید از طریق این صفحه به پرداخت‌های سایر مجموعه‌ها دسترسی یابند.
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);

        $filters = $request->validated();

        return Inertia::render('Portal/Payments/ReviewQueue', [
            'payments' => $action->handle($filters),
            'filters' => [
                'search' => $filters['search'] ?? '',
                'gateway' => $filters['gateway'] ?? '',
                'from' => $filters['from'] ?? '',
                'to' => $filters['to'] ?? '',
            ],
        ]);
    }
}
