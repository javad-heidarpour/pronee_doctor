<?php

namespace App\Actions\Portal\Gallery;

use App\Models\DoctorImage;
use Illuminate\Support\Facades\Storage;

class DeleteGalleryImageAction
{
    public function handle(DoctorImage $image): void
    {
        Storage::disk('public')->delete($image->image_url);
        $image->delete();
    }
}
