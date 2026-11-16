<?php

namespace App\Actions\Portal\Gallery;

use App\Models\DoctorImage;

class UpdateGalleryImageAction
{
    public function handle(DoctorImage $image, array $data): DoctorImage
    {
        $image->update($data);

        return $image;
    }
}
