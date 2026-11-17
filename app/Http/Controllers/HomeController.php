<?php

namespace App\Http\Controllers;

use App\Actions\Home\BookAppointmentAction;
use App\Actions\Home\ListActiveDoctorsAction;
use App\Actions\Home\ShowDoctorProfileAction;
use App\DTOs\Home\BookAppointmentData;
use App\Http\Requests\BookAppointmentRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function Home(ListActiveDoctorsAction $action)
    {
        return Inertia::render('Home', [
            'doctors' => $action->handle(),
        ]);
    }

    public function showDoctor(
        string $slug,
        ShowDoctorProfileAction $action
    ) {
        try {
            return $action->handle($slug);
        } catch (ModelNotFoundException $exception) {
            return Inertia::render('Errors/DoctorNotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }
    }

    public function bookAppointment(
        BookAppointmentRequest $request,
        BookAppointmentAction $action
    ): RedirectResponse {
        $appointment = $action->handle(
            BookAppointmentData::fromValidated($request->validated())
        );

        return back()->with([
            'success' => true,
            'message' => 'نوبت با موفقیت ثبت شد.',
            'appointment_id' => $appointment->id,
            'fee_rial' => $appointment->fee_rial,
        ]);
    }
}
