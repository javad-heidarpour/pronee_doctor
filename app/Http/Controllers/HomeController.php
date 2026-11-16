<?php

namespace App\Http\Controllers;

use App\Actions\Home\BookAppointmentAction;
use App\Actions\Home\ListActiveDoctorsAction;
use App\Actions\Home\ShowDoctorProfileAction;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
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
     * ثبت نوبت
     */
    public function bookAppointment(
        Request $request,
        BookAppointmentAction $action
    ) {
        return $action->handle($request);
    }
}
