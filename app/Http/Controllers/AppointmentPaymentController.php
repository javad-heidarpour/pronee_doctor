<?php

namespace App\Http\Controllers;

use App\Actions\Payments\InitiateAppointmentPaymentAction;
use App\Actions\Payments\VerifyAndSettleSepPaymentAction;
use App\Actions\Payments\CancelFailedAppointmentAction;
use App\Actions\Payments\FindSepCallbackPaymentAction;
use App\Actions\Payments\ProcessLocalSepPaymentCallbackAction;
use App\Actions\Payments\ValidateAppointmentPaymentPhoneAction;
use App\Actions\Payments\ValidateSepCallbackTerminalAction;
use App\Actions\Payments\RecordSepCallbackFailureAction;
use App\Models\Appointment;
use App\Actions\Payments\SendPaidAppointmentConfirmationAction;
use App\Services\Payments\SepPaymentService;
use Illuminate\Http\RedirectResponse;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Log;

class AppointmentPaymentController extends Controller
{



    public function pay(
        int $visit_number,
        SepPaymentService $sep,
        InitiateAppointmentPaymentAction $action,
        ValidateAppointmentPaymentPhoneAction $phoneAction
    ): \Symfony\Component\HttpFoundation\Response {
        $appointment = Appointment::query()->findOrFail($visit_number);

        // صفحه جزئیات نوبت شامل نام بیمار است؛ لینک آن باید امضاشده و زمان‌دار باشد.
        $visitUrl = fn () => redirect()->to(URL::temporarySignedRoute(
            'visit.show',
            now()->addMinutes(30),
            ['visit_number' => $appointment->id]
        ));

        try {
            Log::info('PAY METHOD ENTERED', [
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
            ]);

            $cellNumber = $phoneAction->handle($appointment);

            $token = $action->handle($appointment, $cellNumber, $sep);

            if ($token === null) {
                return $visitUrl()->with(
                    'success',
                    'این نوبت قبلاً پرداخت شده است.'
                );
            }

            return response()->view('payments.sep-redirect', [
                'token' => $token,
            ]);
        } catch (Throwable $e) {
            Log::error('APPOINTMENT PAYMENT REQUEST FAILED', [
                'visit_number' => $appointment->id,
                'exception_class' => get_class($e),
                // Do not persist provider/client exception messages; they may contain secrets.
            ]);

            return $visitUrl()->withErrors([
                'payment' => 'درخواست پرداخت انجام نشد. لطفاً دوباره تلاش کنید.',
            ]);
        }
    }


    public function callback(
        Request $request,
        SepPaymentService $sep,
        ProcessLocalSepPaymentCallbackAction $localCallbackAction,
        VerifyAndSettleSepPaymentAction $settlementAction,
        CancelFailedAppointmentAction $cancelFailedAppointment,
        FindSepCallbackPaymentAction $lookupAction,
        ValidateSepCallbackTerminalAction $terminalAction,
        SendPaidAppointmentConfirmationAction $notificationAction,
        RecordSepCallbackFailureAction $failureAction
    ): RedirectResponse {
        $resNum = trim((string) $request->input('ResNum', ''));
        $refNum = trim((string) $request->input('RefNum', ''));
        $terminalId = trim((string) $request->input('TerminalId', ''));

        $payment = null;
        $appointment = null;

        // صفحه ویزیت فقط با امضای موقت قابل مشاهده است؛ callback نیز
        // باید همان قرارداد امنیتی را رعایت کند.
        $visitRedirect = function (int $appointmentId) {
            return redirect()->to(URL::temporarySignedRoute(
                'visit.show',
                now()->addMinutes(30),
                ['visit_number' => $appointmentId]
            ));
        };

        try {
            /*
            |--------------------------------------------------------------------------
            | 1. تست محلی؛ فقط در محیط local
            |--------------------------------------------------------------------------
            */
            if (
                app()->environment('local') &&
                $request->boolean('test_mode')
            ) {
                if ($resNum === '') {
                    return redirect()->route('home')->withErrors([
                        'payment' => 'ResNum ارسال نشده است.',
                    ]);
                }

                $testResult = (string) $request->input(
                    'test_result',
                    'success'
                );

                if (!in_array($testResult, ['success', 'fail'], true)) {
                    return redirect()->route('home')->withErrors([
                        'payment' => 'نوع تست معتبر نیست.',
                    ]);
                }

                $localResult = $localCallbackAction->handle($resNum, $testResult);

                if ($localResult['status'] === 'payment_not_found') {
                    return redirect()->route('home')->withErrors([
                        'payment' => 'رکورد پرداخت پیدا نشد.',
                    ]);
                }

                if ($localResult['status'] === 'appointment_not_found') {
                    return redirect()->route('home')->withErrors([
                        'payment' => 'نوبت مرتبط با پرداخت پیدا نشد.',
                    ]);
                }

                $appointmentId = (int) $localResult['appointment_id'];

                if ($localResult['status'] === 'already_paid') {
                    return $visitRedirect($appointmentId)->with(
                        'payment_result',
                        [
                            'status' => 'success',
                            'message' => 'برای این نوبت یک پرداخت موفق ثبت شده است.',
                        ]
                    );
                }

                if ($localResult['status'] === 'reservation_not_payable') {
                    return $visitRedirect($appointmentId)->withErrors([
                        'payment' => 'این رزرو یا تلاش پرداخت دیگر امکان تغییر وضعیت در تست را ندارد.',
                    ]);
                }

                if ($localResult['status'] === 'waiting') {
                    return $visitRedirect($appointmentId)->with(
                        'payment_result',
                        [
                            'status' => 'pending',
                            'message' => 'یک تلاش پرداخت دیگر هنوز تعیین‌تکلیف نشده است؛ رزرو آزاد نشد.',
                        ]
                    );
                }

                return $visitRedirect($appointmentId)->with(
                    'payment_result',
                    [
                        'status' => $localResult['status'],
                        'message' => $localResult['status'] === 'success'
                            ? 'تست محلی موفق بود؛ پرداخت واقعی نیست.'
                            : 'تست محلی ناموفق بود؛ پرداخت واقعی نیست.',
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 2. بررسی پارامترهای callback واقعی
            |--------------------------------------------------------------------------
            */
            if ($resNum === '' || $refNum === '' || $terminalId === '') {
                Log::warning('SEP callback missing parameters', [
                    'has_res_num' => $resNum !== '',
                    'has_ref_num' => $refNum !== '',
                    'has_terminal_id' => $terminalId !== '',
                    'request_keys' => array_keys($request->all()),
                ]);

                return redirect()->route('home')->withErrors([
                    'payment' => 'اطلاعات بازگشتی درگاه ناقص است.',
                ]);
            }

            if (! $terminalAction->handle($terminalId)) {
                Log::error('SEP callback terminal mismatch', [
                    'res_num' => $resNum,
                    'callback_terminal' => $terminalId,
                ]);

                return redirect()->route('home')->withErrors([
                    'payment' => 'شناسه ترمینال درگاه معتبر نیست.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 3. یافتن رکورد پرداخت
            |--------------------------------------------------------------------------
            */
            $lookup = $lookupAction->handle($resNum);

            if ($lookup['status'] === 'payment_not_found') {
                Log::error('SEP callback payment not found', [
                    'res_num' => $resNum,
                    'ref_num' => $refNum,
                ]);

                return redirect()->route('home')->withErrors([
                    'payment' => 'رکورد پرداخت پیدا نشد.',
                ]);
            }

            if ($lookup['status'] === 'appointment_not_found') {
                Log::error('SEP callback appointment not found', [
                    'payment_id' => $lookup['payment']->id,
                    'appointment_id' => $lookup['payment']->appointment_id,
                ]);

                return redirect()->route('home')->withErrors([
                    'payment' => 'نوبت مرتبط با پرداخت پیدا نشد.',
                ]);
            }

            $payment = $lookup['payment'];
            $appointment = $lookup['appointment'];

            $settlement = $settlementAction->handle(
                $payment,
                $appointment,
                $refNum,
                $terminalId,
                $sep,
                $cancelFailedAppointment
            );

            if ($settlement['status'] === 'failed') {
                return $visitRedirect($appointment->id)->withErrors([
                    'payment' => 'پرداخت توسط درگاه تأیید نشد و رزرو لغو شد. در صورت کسر وجه، پیگیری بانکی انجام می‌شود.',
                ]);
            }

            if ($settlement['status'] === 'waiting') {
                return $visitRedirect($appointment->id)->withErrors([
                    'payment' => 'این تلاش پرداخت ناموفق بود، اما تلاش پرداخت دیگری هنوز تعیین‌تکلیف نشده است؛ رزرو فعلاً حفظ شده است.',
                ]);
            }

            if ($settlement['status'] === 'verification_mismatch') {
                return $visitRedirect($appointment->id)->withErrors([
                    'payment' => 'شناسه مرجع یا ترمینال پاسخ استعلام با تراکنش ثبت‌شده مطابقت ندارد؛ پرداخت ثبت نشد و نیازمند بررسی است.',
                ]);
            }

            if ($settlement['status'] === 'amount_mismatch') {
                return $visitRedirect($appointment->id)->withErrors([
                    'payment' => 'مبلغ تراکنش نیازمند بررسی است. لطفاً دوباره پرداخت نکنید.',
                ]);
            }

            if (!in_array($settlement['status'], ['success', 'already_paid'], true)) {
                return $visitRedirect($appointment->id)->withErrors([
                    'payment' => 'وضعیت پرداخت با وضعیت رزرو همخوانی ندارد و نیازمند بررسی است. لطفاً پرداخت مجدد انجام ندهید.',
                ]);
            }

            $alreadyPaid = $settlement['status'] === 'already_paid';

            // ارسال پیامک فقط پس از تأیید موفق پرداخت
            if (!$alreadyPaid) {
                $notificationAction->handle((int) $appointment->id);
            }

            return $visitRedirect($appointment->id)->with(
                'payment_result',
                [
                    'status' => 'success',
                    'message' => $alreadyPaid
                        ? 'این پرداخت قبلاً ثبت شده است.'
                        : 'پرداخت با موفقیت تأیید و ثبت شد.',
                ]
            );

        } catch (Throwable $e) {
            $failureAction->handle($payment, $resNum, $refNum, $e);

            if ($appointment) {
                return $visitRedirect($appointment->id)->withErrors([
                    'payment' =>
                        'بررسی پرداخت با خطا مواجه شد. لطفاً پرداخت مجدد انجام ندهید و وضعیت تراکنش را بررسی کنید.',
                ]);
            }

            return redirect()->route('home')->withErrors([
                'payment' => 'پردازش پرداخت با خطا مواجه شد.',
            ]);
        }
    }
}