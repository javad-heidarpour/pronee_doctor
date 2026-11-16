<?php

namespace App\Actions\Portal\DoctorProfile;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateDoctorProfileAction
{
    public function handle(array $data, ?UploadedFile $photo): void
    {
        $doctor = Auth::guard('web')->user()->doctorProfile;

        if ($photo) {
            if ($doctor->photo) {
                Storage::disk('public')->delete($doctor->photo);
            }

            $data['photo'] = $photo->store('doctors', 'public');
        }

        // تولید slug جدید اگر نام تغییر کرده
        if ($doctor->name !== $data['name'] || $doctor->family !== $data['family']) {
            $data['slug'] = Str::slug($data['name'] . ' ' . $data['family']) . '-' . uniqid();
        }

        $doctor->update($data);
    }
}
