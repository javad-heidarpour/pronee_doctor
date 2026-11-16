<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Actions\Home\ListActiveDoctorsAction;
use App\Actions\Home\ShowDoctorProfileAction;
use App\Actions\Home\BookAppointmentAction;
use App\Models\Doctor;
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

        $doctor = Doctor::where('slug', $slug)->first();
        if (!$doctor) {
            return Inertia::render('Errors/DoctorNotFound')
                ->toResponse(request())
                ->setStatusCode(404);
        }

        return $action->handle($doctor->slug);
    }

    /**
     * ثبت نوبت
     */
    public function bookAppointment(
        Request $request,
        BookAppointmentAction $action
    ) {
        return $action->handle($request);
    }
}
