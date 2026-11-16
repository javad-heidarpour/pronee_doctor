<?php

namespace App\Actions\Portal\Gallery;

use App\Models\DoctorImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;

class StoreGalleryImagesAction
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function handle(array $files, string $category, ?string $caption): void
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;
        $lastOrder = $doctor->images()->max('sort_order') ?? 0;

        foreach ($files as $file) {
            $path = $file->store('doctor-gallery', 'public');

            DoctorImage::create([
                'doctor_id' => $doctor->id,
                'image_url' => $path,
                'caption' => $caption,
                'category' => $category,
                'sort_order' => ++$lastOrder,
                'is_active' => true,
            ]);
        }
    }
}
