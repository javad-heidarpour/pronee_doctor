<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Doctor;

final class FindPublicDoctorBySlugAction
{
    public function handle(string $slug): Doctor
    {
        $doctor = Doctor::query()
            ->select([
                'id',
                'city_id',
                'specialty_id',
                'caption',
                'name',
                'family',
                'slug',
                'photo',
                'banner',
                'description',
                'meta_title',
                'meta_description',
                'specialty_title',
                'years_of_experience',
                'satisfaction_rate',
            ])
            ->with([
                'city:id,name,province',
                'specialty:id,name,slug,icon,description',
                'address' => fn ($query) => $query
                    ->select([
                        'id',
                        'doctor_id',
                        'name',
                        'full_address',
                        'city_id',
                        'phone',
                        'Latitude',
                        'Longitude',
                    ])
                    ->where('is_active', true),
                'address.city:id,name',
                'socials' => fn ($query) => $query
                    ->select(['id', 'doctor_id', 'platform_id', 'username', 'url'])
                    ->where('is_visible', true)
                    ->whereHas('platform', fn ($platform) => $platform->where('is_active', true)),
                'socials.platform:id,name,title,base_url,icon,color',
                'honorBadges' => fn ($query) => $query->select([
                    'id',
                    'doctor_id',
                    'title',
                    'icon',
                    'sort_order',
                ]),
            ])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        return $doctor->hidePrivatePublicFields();
    }
}
