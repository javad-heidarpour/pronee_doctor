<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Address;
use App\Models\Doctor;
use App\Models\DoctorSocial;
use App\Models\HonorBadge;

final class BuildPublicDoctorProfilePayloadAction
{
    public function handle(Doctor $doctor): array
    {
        $payload = $doctor->only([
            'id',
            'caption',
            'name',
            'family',
            'slug',
            'city_id',
            'specialty_id',
            'specialty_title',
            'photo',
            'banner',
            'description',
            'meta_title',
            'meta_description',
            'years_of_experience',
            'satisfaction_rate',
        ]);

        $payload['city'] = $doctor->city?->only(['id', 'name', 'province']);
        $payload['specialty'] = $doctor->specialty?->only([
            'id',
            'name',
            'slug',
            'icon',
            'description',
        ]);

        $payload['address'] = $doctor->address
            ->map(fn (Address $clinic): array => [
                'id' => $clinic->id,
                'name' => $clinic->name,
                'full_address' => $clinic->full_address,
                'city_id' => $clinic->city_id,
                'phone' => $clinic->phone,
                'Latitude' => $clinic->Latitude,
                'Longitude' => $clinic->Longitude,
                'city' => $clinic->city?->only(['id', 'name']),
            ])
            ->values()
            ->all();

        $payload['socials'] = $doctor->socials
            ->map(fn (DoctorSocial $social): array => [
                'id' => $social->id,
                'username' => $social->username,
                'url' => $social->url,
                'platform' => $social->platform?->only([
                    'name',
                    'title',
                    'base_url',
                    'icon',
                    'color',
                ]),
            ])
            ->filter(fn (array $social): bool => is_array($social['platform']))
            ->values()
            ->all();

        $payload['honor_badges'] = $doctor->honorBadges
            ->map(fn (HonorBadge $badge): array => $badge->only([
                'id',
                'title',
                'icon',
                'sort_order',
            ]))
            ->values()
            ->all();

        $photo = $doctor->photo;
        $payload['photo_url'] = $photo
            ? (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://')
                ? $photo
                : (str_starts_with($photo, '/')
                    ? url($photo)
                    : (str_starts_with($photo, 'storage/')
                        ? url('/' . ltrim($photo, '/'))
                        : asset('storage/' . ltrim($photo, '/')))))
            : null;

        return $payload;
    }
}
