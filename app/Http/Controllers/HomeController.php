<?php

namespace App\Http\Controllers;

use App\Actions\Home\BookAppointmentAction;
use App\Actions\Home\ListActiveDoctorsAction;
use App\Actions\Home\ShowDoctorProfileAction;
use App\DTOs\Home\BookAppointmentData;
use App\Models\Appointment;
use Illuminate\Support\Facades\URL;
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

    /**
     * Legacy success endpoint retained for existing links. Use the same
     * temporary signed visit URL as the payment callback; never expose a
     * patient's appointment details through an unsigned numeric identifier.
     */
    public function appointmentSuccess(int $id): RedirectResponse
    {
        $appointment = Appointment::query()->findOrFail($id);

        return redirect()->to(URL::temporarySignedRoute(
            'visit.show',
            now()->addMinutes(30),
            ['visit_number' => $appointment->id]
        ));
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
            'payment_url' => URL::temporarySignedRoute(
                'payment.pay',
                now()->addMinutes(15),
                ['visit_number' => $appointment->id]
            ),
        ]);
    }
}
