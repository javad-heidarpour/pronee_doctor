<?php

namespace App\Http\Controllers;

use App\Actions\Sms\SendAppointmentConfirmationSmsAction;
use App\Actions\Sms\SendVisitOtpSmsAction;
use Illuminate\Http\JsonResponse;

class SmsController extends Controller
{
    public function visit(
        int $visit_number,
        SendVisitOtpSmsAction $action
    ): JsonResponse {
        return $action->handle($visit_number);
    }

    public function AppointmentConfirmationSMS(
        int $visit_number,
        SendAppointmentConfirmationSmsAction $action
    ): JsonResponse {
        return $action->handle($visit_number);
    }
}
