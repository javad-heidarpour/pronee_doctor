<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Actions\Home\HomeIndexAction;
use App\Actions\Home\ShowDoctorProfileAction;
use App\Actions\Home\BookAppointmentAction;
use App\Models\Doctor;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function Home()
    {


        $doctors = Doctor::query()
            ->where('is_active', 1)
            ->select([
                'id',
                'slug',
                'caption',
                'name',
                'family',
                'specialty_title',
                'photo',
            ])
            ->orderBy('id')
            ->get();

        return Inertia::render('Home', [
            'doctors' => $doctors,
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
