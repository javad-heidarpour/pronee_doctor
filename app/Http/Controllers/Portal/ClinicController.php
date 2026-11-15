<?php

// app/Http/Controllers/Doctor/ClinicController.php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Models\City;

class ClinicController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        $doctor = $user->doctorProfile;
        $clinics = $doctor->address()->with('city')->get();
        $cities = City::all();
        // dd(  $cities);
        return Inertia::render('Portal/Clinics', [
            'clinics' => $clinics,
            'doctor' => $doctor,
            'cities' => $cities

        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255',
            'full_address' => 'required|string',
            'phone' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);
          $user = Auth::guard('web')->user();

    

        // دریافت پروفایل پزشک
        $doctor = $user->doctorProfile;
        $validated['doctor_id'] =  $doctor->id;
        // dd(   $validated );

        Address::create($validated);

        return back()->with('success', 'مطب با موفقیت اضافه شد.');
    }

    public function update(Request $request, Address $clinic)
    {
        $this->authorizeClinic($clinic);

        $validated = $request->validate([
            'city_id' => 'required|exists:cities,id',
            'name' => 'required|string|max:255',
            'full_address' => 'required|string',
            'phone' => 'nullable|string',
            'lat' => 'nullable|numeric',
            'lng' => 'nullable|numeric',
        ]);

        $clinic->update($validated);

        return back()->with('success', 'اطلاعات مطب بروزرسانی شد.');
    }

    public function destroy(Address $clinic)
    {
        $this->authorizeClinic($clinic);
        $clinic->delete();

        return back()->with('success', 'مطب حذف شد.');
    }

    private function authorizeClinic(Address $clinic)
    {
        if ($clinic->doctor_id !== Auth::guard('doctor')->id()) {
            abort(403);
        }
    }
}