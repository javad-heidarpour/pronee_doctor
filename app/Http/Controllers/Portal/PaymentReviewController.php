<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Payments\ListPaymentsRequiringReviewAction;
use App\Actions\Payments\ReconcileKnownSepPaymentAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\AppointmentPayment;
use RuntimeException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ListPaymentReviewRequest;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class PaymentReviewController extends Controller
{
    /**
     * Gateway reconciliation queue is restricted to system administrators.
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
        } catch (RuntimeException $exception) {
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

    public function index(
        ListPaymentReviewRequest $request,
        ListPaymentsRequiringReviewAction $action
    ): Response {
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
    }}
