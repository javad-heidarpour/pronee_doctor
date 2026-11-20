<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Doctor;

final class FindPublicDoctorBySlugAction
{
    public function handle(string $slug): Doctor
    {
        $doctor = Doctor::query()
            ->with([
                'city',
                'specialty',
                'address' => fn ($query) => $query->where('is_active', true),
                'address.city',
                'socials' => fn ($query) => $query
                    ->where('is_visible', true)
                    ->whereHas('platform', fn ($platform) => $platform->where('is_active', true)),
                'socials.platform',
                'honorBadges',
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return $doctor->hidePrivatePublicFields();
    }
}
