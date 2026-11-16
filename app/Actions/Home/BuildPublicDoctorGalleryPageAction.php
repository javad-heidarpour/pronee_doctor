<?php

namespace App\\Actions\\Home;

use App\\Models\\Doctor;
use App\\Models\\DoctorImage;

class BuildPublicDoctorGalleryPageAction
{
    public function handle(Doctor $doctor): array
    {
        $galleryImages = $doctor->activeImages()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'image_url' => $image->full_image_url,
                    'caption' => $image->caption,
                    'category' => $image->category,
                    'category_name' => $image->category_name,
                    'sort_order' => $image->sort_order,
                ];
            });

        $galleryCategories = $doctor->activeImages()
            ->selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'value' => $item->category,
                    'label' => DoctorImage::CATEGORIES[$item->category] ?? 'سایر',
                    'count' => $item->count,
                ];
            })
            ->sortBy('label')
            ->values();

        return [
            'galleryCategories' => $galleryCategories,
            'galleryImages' => $galleryImages,
        ];
    }
}
