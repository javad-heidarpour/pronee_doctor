<?php

namespace App\\Actions\\Portal\\DoctorProfile;

use App\\Models\\Doctor;
use Illuminate\\Http\\UploadedFile;
use Illuminate\\Support\\Facades\\Storage;
use Illuminate\\Support\\Str;

class UpdateDoctorProfileAction
{
    public function handle(
        Doctor $doctor,
        array $data,
        ?UploadedFile $photo
    ): void {
        if ($photo) {
            if ($doctor->photo) {
                Storage::disk('public')->delete($doctor->photo);
            }

            $data['photo'] = $photo->store('doctors', 'public');
        }

        if (
            isset($data['name'], $data['family']) &&
            (
                $doctor->name !== $data['name'] ||
                $doctor->family !== $data['family']
            )
        ) {
            $data['slug'] = Str::slug(
                $data['name'] . ' ' . $data['family']
            ) . '-' . uniqid();
        }

        $doctor->update($data);
    }
}