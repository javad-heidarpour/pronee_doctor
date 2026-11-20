<?php

namespace App\Http\Controllers;

use App\Actions\Visit\ShowVisitAction;
use App\Actions\Visit\CancelPublicAppointmentAction;
use App\Http\Requests\CancelPublicAppointmentRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    public function show(int $visit_number, ShowVisitAction $action): Response
    {
        return Inertia::render('Visit/Show', [
            'visit' => $action->handle($visit_number),
        ]);
    }

    public function cancel(
        int $visit_number,
        CancelPublicAppointmentRequest $request,
        CancelPublicAppointmentAction $action
    ): RedirectResponse {
        $result = $action->handle(
            $visit_number,
            (string) $request->validated('patient_phone')
        );

        return back()->with(
            'success',
            $result === 'already_cancelled'
                ? 'این نوبت قبلاً لغو شده است.'
                : 'نوبت شما با موفقیت لغو شد و ساعت آن آزاد شد.'
        );
    }
}
