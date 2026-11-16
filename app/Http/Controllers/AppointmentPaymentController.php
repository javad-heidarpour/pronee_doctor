<?php

namespace App\Http\Controllers;

use App\Actions\Payments\InitiateAppointmentPaymentAction;
use App\Actions\Payments\CancelFailedAppointmentAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Http\Controllers\SmsController;
use App\Services\Payments\SepPaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentPaymentController extends Controller
{



    public function pay(
        int $visit_number,
        InitiateAppointmentPaymentAction $action
    ): \Symfony\Component\HttpFoundation\Response {
        $appointment = Appointment::query()->findOrFail($visit_number);

        $visitUrl = fn () => redirect()->route('visit.show', [
            'visit_number' => $appointment->id,
        ]);

        try {
            Log::info('PAY METHOD ENTERED', [
                'appointment_id' => $appointment->id,
                'status' => $appointment->status,
            ]);

            if (!in_array($appointment->status, ['pending', 'confirmed'], true)) {
                return $visitUrl()->withErrors([
                    'payment' => 'این نوبت امکان پرداخت ندارد.',
                ]);
            }

            $amount = (int) $appointment->fee_rial;

            if ($amount <= 0) {
                return $visitUrl()->withErrors([
                    'payment' => 'مبلغ ویزیت معتبر نیست.',
                ]);
            }

            $cellNumber = trim((string) $appointment->patient_phone);

            if (!preg_match('/^09\d{9}$/', $cellNumber)) {
                return $visitUrl()->withErrors([
                    'payment' => 'شماره موبایل بیمار معتبر نیست.',
                ]);
            }

            $token = $action->handle($appointment, $cellNumber);

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
            report($e);

            Log::error('APPOINTMENT PAYMENT REQUEST FAILED', [
                'visit_number' => $appointment->id,
                'exception_class' => get_class($e),
                'exception' => $e->getMessage(),
            ]);

            return $visitUrl()->withErrors([
                'payment' => $e instanceof \RuntimeException
                    ? $e->getMessage()
                    : 'درخواست پرداخت انجام نشد. لطفاً دوباره تلاش کنید.',
            ]);
        }
    }


    public function callback(
        Request $request,
        SepPaymentService $sep,
        CancelFailedAppointmentAction $cancelFailedAppointment
    ): RedirectResponse {
        $resNum = trim((string) $request->input('ResNum', ''));
        $refNum = trim((string) $request->input('RefNum', ''));
        $terminalId = trim((string) $request->input('TerminalId', ''));

        $payment = null;
        $appointment = null;

        $visitRedirect = function (int $appointmentId) {
            return redirect()->to(url('/visit/' . $appointmentId));
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

                $payment = AppointmentPayment::query()
                    ->where('payment_uuid', $resNum)
                    ->where('gateway_name', 'sep')
                    ->first();

                if (!$payment) {
                    return redirect()->route('home')->withErrors([
                        'payment' => 'رکورد پرداخت پیدا نشد.',
                    ]);
                }

                $appointment = Appointment::find($payment->appointment_id);

                if (!$appointment) {
                    return redirect()->route('home')->withErrors([
                        'payment' => 'نوبت مرتبط با پرداخت پیدا نشد.',
                    ]);
                }

                $testError = null;

                DB::transaction(function () use ($payment, $appointment, $testResult, &$testError) {
                    $lockedPayment = AppointmentPayment::query()
                        ->whereKey($payment->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    $lockedAppointment = Appointment::query()
                        ->whereKey($appointment->id)
                        ->lockForUpdate()
                        ->firstOrFail();

                    if ($lockedPayment->status === 'paid') {
                        $testError = 'already_paid';
                        return;
                    }

                    if ($testResult === 'fail') {
                        $lockedPayment->update([
                            'status' => 'failed',
                            'gateway_result_code' => 'LOCAL_TEST_FAILED',
                            'gateway_result_message' =>
                                'تست محلی؛ تراکنش واقعی انجام نشده است.',
                            'paid_at' => null,
                            'gateway_verified_at' => null,
                        ]);

                        return;
                    }

                    $lockedPayment->update([
                        'status' => 'paid',
                        'ref_num' => 'LOCAL-TEST-' . $lockedPayment->id,
                        'terminal_id' => 'LOCAL-TEST',
                        'gateway_result_code' => 'LOCAL_TEST_SUCCESS',
                        'gateway_result_message' =>
                            'تست محلی؛ پرداخت واقعی SEP نیست.',
                        'paid_at' => now(),
                        'gateway_verified_at' => null,
                    ]);

                    if (
                        \Illuminate\Support\Facades\Schema::hasColumn(
                            'appointments',
                            'payment_status'
                        )
                    ) {
                        $lockedAppointment->payment_status = 'paid';
                    }

                    $lockedAppointment->status = 'confirmed';

                    $lockedAppointment->save();
                });

                if ($testError === 'already_paid') {
                    return $visitRedirect($appointment->id)->withErrors([
                        'payment' => 'این پرداخت قبلاً ثبت شده است.',
                    ]);
                }

                // تست ناموفق باید وضعیت رزرو و آزادسازی اسلات را نیز شبیه‌سازی کند.
                if ($testResult === 'fail') {
                    $cancelFailedAppointment->handle($payment);
                }

                return $visitRedirect($appointment->id)->with(
                    'payment_result',
                    [
                        'status' => $testResult === 'success'
                            ? 'success'
                            : 'failed',
                        'message' => $testResult === 'success'
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

            $configuredTerminalId = trim(
                (string) config('services.sep.terminal_id', '')
            );

            if (
                $configuredTerminalId === '' ||
                !hash_equals($configuredTerminalId, $terminalId)
            ) {
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
            $payment = AppointmentPayment::query()
                ->where('payment_uuid', $resNum)
                ->where('gateway_name', 'sep')
                ->first();

            if (!$payment) {
                Log::error('SEP callback payment not found', [
                    'res_num' => $resNum,
                    'ref_num' => $refNum,
                ]);

                return redirect()->route('home')->withErrors([
                    'payment' => 'رکورد پرداخت پیدا نشد.',
                ]);
            }

            $appointment = Appointment::find($payment->appointment_id);

            if (!$appointment) {
                Log::error('SEP callback appointment not found', [
                    'payment_id' => $payment->id,
                    'appointment_id' => $payment->appointment_id,
                ]);

                return redirect()->route('home')->withErrors([
                    'payment' => 'نوبت مرتبط با پرداخت پیدا نشد.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 4. اگر قبلاً پرداخت تأیید شده است، دوباره پردازش نکن
            |--------------------------------------------------------------------------
            */
            if ($payment->status === 'paid') {
                return $visitRedirect($appointment->id)->with(
                    'payment_result',
                    [
                        'status' => 'success',
                        'message' => 'این پرداخت قبلاً ثبت شده است.',
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 5. ثبت اطلاعات اولیه callback
            |--------------------------------------------------------------------------
            */
            $payment->update([
                'ref_num' => $refNum,
                'terminal_id' => $terminalId,
                'gateway_result_message' =>
                    'در انتظار تأیید تراکنش از سامان‌کیش.',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 6. استعلام تراکنش از SEP
            |--------------------------------------------------------------------------
            */
            $verifyResponse = $sep->verifyTransaction($refNum);

            if (
                !is_array($verifyResponse) ||
                !array_key_exists('ResultCode', $verifyResponse)
            ) {
                Log::error('SEP invalid verification response', [
                    'payment_id' => $payment->id,
                    'ref_num' => $refNum,
                ]);

                throw new \RuntimeException(
                    'پاسخ استعلام سامان‌کیش معتبر نیست.'
                );
            }

            $resultCode = $verifyResponse['ResultCode'];

            $resultMessage = (string) (
                $verifyResponse['ResultDescription']
                ?? $verifyResponse['ResultMessage']
                ?? $verifyResponse['Message']
                ?? ''
            );

            $detail = $verifyResponse['TransactionDetail'] ?? [];

            Log::info('SEP VERIFY RESPONSE', [
                'payment_id' => $payment->id,
                'ref_num' => $refNum,
                'result_code' => $resultCode,
                'result_message' => $resultMessage,
                'response_keys' => array_keys($verifyResponse),
                'detail_keys' => is_array($detail)
                    ? array_keys($detail)
                    : [],
                'original_amount' => is_array($detail)
                    ? ($detail['OrginalAmount'] ?? null)
                    : null,
                'affective_amount' => is_array($detail)
                    ? ($detail['AffectiveAmount'] ?? null)
                    : null,
            ]);

            $payment->update([
                'gateway_result_code' => is_numeric($resultCode)
                    ? (string) $resultCode
                    : null,
                'gateway_result_message' => $resultMessage !== ''
                    ? $resultMessage
                    : 'پاسخ استعلام از درگاه دریافت شد.',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 7. نتیجه استعلام باید موفق باشد
            |--------------------------------------------------------------------------
            */
            if (!is_numeric($resultCode) || (int) $resultCode !== 0) {
                // فقط اگر نتیجه درگاه به‌طور قطعی ناموفق است:
                Log::warning('SEP verification unsuccessful', [
                    'payment_id' => $payment->id,
                    'ref_num' => $refNum,
                    'result_code' => $resultCode,
                    'result_message' => $resultMessage,
                ]);

                $cancelFailedAppointment->handle($payment);

                return $visitRedirect($appointment->id)->withErrors([
                    'payment' => 'پرداخت توسط درگاه تأیید نشد و رزرو لغو شد. در صورت کسر وجه، پیگیری بانکی انجام می‌شود.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 8. دریافت و تطبیق مبلغ تراکنش
            |--------------------------------------------------------------------------
            */
            $verifiedAmount = is_array($detail)
                ? ($detail['OrginalAmount'] ?? null)
                : null;

            if (
                !is_numeric($verifiedAmount) ||
                (int) $verifiedAmount !== (int) $payment->amount_rial
            ) {
                Log::error('SEP transaction amount mismatch', [
                    'payment_id' => $payment->id,
                    'ref_num' => $refNum,
                    'expected_amount' => $payment->amount_rial,
                    'verified_amount' => $verifiedAmount,
                    'detail_keys' => is_array($detail)
                        ? array_keys($detail)
                        : [],
                ]);

                $payment->update([
                    'gateway_result_message' =>
                        'مبلغ تراکنش تأییدشده با مبلغ رکورد پرداخت مطابقت ندارد؛ بررسی دستی لازم است.',
                ]);

                return $visitRedirect($appointment->id)->withErrors([
                    'payment' =>
                        'مبلغ تراکنش نیازمند بررسی است. لطفاً دوباره پرداخت نکنید.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | 9. ثبت اتمیک پرداخت موفق
            |--------------------------------------------------------------------------
            */
            $alreadyPaid = DB::transaction(function () use ($payment, $appointment, $refNum, $terminalId, $resultCode, $resultMessage) {
                $lockedPayment = AppointmentPayment::query()
                    ->whereKey($payment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedAppointment = Appointment::query()
                    ->whereKey($appointment->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($lockedPayment->status === 'paid') {
                    return true;
                }

                $lockedPayment->update([
                    'status' => 'paid',
                    'ref_num' => $refNum,
                    'terminal_id' => $terminalId,
                    'gateway_result_code' => (string) $resultCode,
                    'gateway_result_message' => $resultMessage !== ''
                        ? $resultMessage
                        : 'تراکنش توسط سامان‌کیش تأیید شد.',
                    'paid_at' => now(),
                    'gateway_verified_at' => now(),
                ]);

                if (
                    \Illuminate\Support\Facades\Schema::hasColumn(
                        'appointments',
                        'payment_status'
                    )
                ) {
                    $lockedAppointment->payment_status = 'paid';
                }

                // پرداخت تأییدشده باید نوبت را نیز قطعی کند.
                $lockedAppointment->status = 'confirmed';
                $lockedAppointment->reservation_expires_at = null;
                $lockedAppointment->save();

                return false;
            });

            Log::info('SEP PAYMENT visited', [
                'payment_id' => $payment->id,
                'appointment_id' => $appointment->id,
                'ref_num' => $refNum,
                'already_paid' => $alreadyPaid,
            ]);

            // ارسال پیامک فقط پس از تأیید موفق پرداخت
            if (!$alreadyPaid) {
                try {
                    $smsResponse = app(SmsController::class)
                        ->AppointmentConfirmationSMS((int) $appointment->id);

                    Log::info('APPOINTMENT CONFIRMATION SMS TRIGGERED', [
                        'appointment_id' => $appointment->id,
                    ]);

                } catch (Throwable $smsException) {
                    // خطای پیامک نباید پرداخت موفق را ناموفق کند
                    Log::error('APPOINTMENT CONFIRMATION SMS FAILED', [
                        'appointment_id' => $appointment->id,
                        'exception' => $smsException->getMessage(),
                    ]);
                }
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
            Log::error('SEP callback exception', [
                'payment_id' => $payment?->id,
                'res_num' => $resNum,
                'ref_num' => $refNum,
                'exception_class' => get_class($e),
                'exception' => $e->getMessage(),
            ]);

            if ($payment && $payment->status !== 'paid') {
                try {
                    $payment->update([
                        'gateway_result_message' =>
                            'خطا در بررسی تراکنش؛ وضعیت نیازمند بررسی است.',
                    ]);
                } catch (Throwable $updateException) {
                    Log::error('SEP payment error update failed', [
                        'payment_id' => $payment->id,
                        'exception' => $updateException->getMessage(),
                    ]);
                }
            }

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