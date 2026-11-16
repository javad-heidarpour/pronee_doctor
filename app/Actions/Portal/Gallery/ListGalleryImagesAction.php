<?php

namespace App\Actions\Portal\Gallery;

use App\Models\DoctorImage;
use Illuminate\Support\Facades\Auth;

class ListGalleryImagesAction
{
    public function handle(): array
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;
        $images = $doctor->images()->orderBy('sort_order')->get();

        return [
            'doctor' => $doctor,
            'images' => $images->map(fn ($img) => [
                'id' => $img->id,
                'image_url' => $img->full_image_url,
                'caption' => $img->caption,
                'category' => $img->category,
                'category_name' => $img->category_name,
                'sort_order' => $img->sort_order,
                'is_active' => $img->is_active,
            ]),
            'categories' => DoctorImage::CATEGORIES,
        ];
    }
}
