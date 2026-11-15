<?php

// app/Http/Controllers/Doctor/GalleryController.php
namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\DoctorImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        $doctor = $user->doctorProfile;
        $images = $doctor->images()->orderBy('sort_order')->get();

        return Inertia::render('Portal/Gallery', [
            'doctor' => $doctor,
            'images' => $images->map(fn($img) => [
                'id' => $img->id,
                'image_url' => $img->full_image_url,
                'caption' => $img->caption,
                'category' => $img->category,
                'category_name' => $img->category_name,
                'sort_order' => $img->sort_order,
                'is_active' => $img->is_active,
            ]),
            'categories' => DoctorImage::CATEGORIES,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'images' => 'required|array|max:10',
            'images.*' => 'image|max:5120',
            'caption' => 'nullable|string|max:500',
            'category' => 'required|in:Workplace,equipment,team,certificate,Heartfelt,Portfolio,Satisfaction,other',

        ]);

        $user = Auth::guard('web')->user();
        $doctor = $user->doctorProfile;
        $lastOrder = $doctor->images()->max('sort_order') ?? 0;


        foreach ($request->file('images') as $index => $file) {
            $path = $file->store('doctor-gallery', 'public');

            DoctorImage::create([
                'doctor_id' => $doctor->id,
                'image_url' => $path,
                'caption' => $validated['caption'] ?? null,
                'category' => $validated['category'],
                'sort_order' => ++$lastOrder,
                'is_active' => true,
            ]);
        }

        return back()->with('success', 'تصاویر با موفقیت اضافه شدند.');
    }

    public function update(Request $request, DoctorImage $image)
    {
        $this->authorizeImage($image);

        $validated = $request->validate([
            'caption' => 'nullable|string|max:255',
            'category' => 'required|in:clinic,equipment,team,certificate,other',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ]);

        $image->update($validated);

        return back()->with('success', 'تصویر بروزرسانی شد.');
    }

    public function destroy(DoctorImage $image)
    {
        $this->authorizeImage($image);

        Storage::disk('public')->delete($image->image_url);
        $image->delete();

        return back()->with('success', 'تصویر حذف شد.');
    }

    private function authorizeImage(DoctorImage $image)
    {
        if ($image->doctor_id !== Auth::guard('doctor')->id()) {
            abort(403);
        }
    }
}