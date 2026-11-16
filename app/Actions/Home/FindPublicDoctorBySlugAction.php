<?php

namespace App\\Actions\\Home;

use App\\Models\\Doctor;

class FindPublicDoctorBySlugAction
{
    public function handle(string $slug): Doctor
    {
        return Doctor::query()
            ->with([
                'city',
                'reviews',
                'specialty',
                'user',
                'address',
                'address.city',
                'timeSlots',
                'socials.platform',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();
    }
}
