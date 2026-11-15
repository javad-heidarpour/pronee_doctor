<?php

namespace App\Actions\Home;
use App\Actions\Blog\Listing\BlogIndexAction;
use App\Http\Controllers\BlogController;
use App\Models\City;
use App\Models\Doctor;
use App\Models\Specialty;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeIndexAction
{
    public function handle(Request $request): Response
    {
        $blogData = app(BlogIndexAction::class)->handle();

        $cityId = $request->input('city');
        $specialtyId = $request->input('specialty');

        $query = Doctor::with([
            'city',
            'specialty',
            'user',
            'workingHours.clinic',
        ]);

        if ($cityId) {
            $query->where('city_id', $cityId);
        }

        $doctors = null;

        if ($specialtyId) {
            $query->where('specialty_id', $specialtyId);
            $doctors = $query->get();
        }

        // return($doctors);
        return Inertia::render('HomePage', [
            'doctors' => $doctors,
            'cities' => City::all(),
            'Specialty' => Specialty::all(),
            'filters' => $request->only([
                'city',
                'specialty',
                'workingHours.clinic',

            ]),
            'blog' => $blogData,
        ]);
    }
}