<?php
// app/Http/Controllers/Doctor/ProfileController.php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function edit()
    {
        $doctor = Auth::guard('doctor')->user()->load(['specialty', 'city']);
        return Inertia::render('Portal/Profile', ['doctor' => $doctor]);
    }

    public function update(Request $request)
    {
$user = Auth::guard('web')->user();
$doctor = $user->doctorProfile;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'family' => 'required|string|max:255',
            'caption' => 'nullable|string|max:255',
            'specialty_title' => 'required|string|max:255',
            'years_of_experience' => 'required|integer|min:0',
            'Medical_system_number' => 'required|string',
            'description' => 'nullable|string',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'photo' => 'nullable|image|max:2048',
            'email' => 'required|email|unique:doctors,email,' . $doctor->id,
            'phone' => 'nullable|string',
        ]);

        if ($request->hasFile('photo')) {
            if ($doctor->photo) {
                Storage::disk('public')->delete($doctor->photo);
            }
            $validated['photo'] = $request->file('photo')->store('doctors', 'public');
        }

        // تولید slug جدید اگر نام تغییر کرده
        if ($doctor->name !== $validated['name'] || $doctor->family !== $validated['family']) {
            $validated['slug'] = Str::slug($validated['name'] . ' ' . $validated['family']) . '-' . uniqid();
        }

        $doctor->update($validated);

        return back()->with('success', 'اطلاعات پروفایل با موفقیت بروزرسانی شد.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'password' => 'required|min:8|confirmed',
        ]);

$user = Auth::guard('web')->user();
$doctor = $user->doctorProfile;

        if (!Hash::check($validated['current_password'], $doctor->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی اشتباه است.']);
        }

 
        // ✅ رمز رو هش کن و بعد ذخیره کن
        $doctor->update([
            'password' => Hash::make($validated['password'])
        ]);

        return back()->with('success', 'رمز عبور با موفقیت تغییر کرد.');
    }
}