<?php

namespace App\\Actions\\Portal\\Gallery;

use App\\Models\\Doctor;
use App\\Models\\DoctorImage;

class ListGalleryImagesAction
{
    public function handle(Doctor $doctor): array
    {
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