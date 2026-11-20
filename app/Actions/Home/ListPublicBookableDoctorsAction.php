<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Doctor;
use Illuminate\Support\Collection;

final class ListPublicBookableDoctorsAction
{
    public function handle(?Doctor $relatedTo = null, ?int $limit = null): Collection
    {
        $query = Doctor::query()
            ->where('is_active', true)
            ->whereHas('address', function ($clinicQuery): void {
                $clinicQuery
                    ->where('is_active', true)
                    ->whereHas('timeSlots', fn ($slotQuery) => $slotQuery->publiclyBookable());
            })
            ->with([
                'address' => function ($clinicQuery): void {
                    $clinicQuery
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
                        ->where('is_active', true)
                        ->whereHas('timeSlots', fn ($slotQuery) => $slotQuery->publiclyBookable())
                        ->with([
                            'city:id,name',
                            'timeSlots' => fn ($slotQuery) => $slotQuery
                                ->select([
                                    'id',
                                    'doctor_id',
                                    'clinic_id',
                                    'date',
                                    'start_time',
                                    'end_time',
                                    'status',
                                ])
                                ->publiclyBookable()
                                ->orderBy('date')
                                ->orderBy('start_time')
                                ->limit(1),
                            'latestPubliclyBookableTimeSlot' => fn ($slotQuery) => $slotQuery
                                ->select([
                                    'id',
                                    'doctor_id',
                                    'clinic_id',
                                    'date',
                                    'start_time',
                                    'end_time',
                                    'status',
                                    'created_at',
                                    'updated_at',
                                ]),
                        ]);
                },
            ])
            ->select([
                'id',
                'city_id',
                'specialty_id',
                'slug',
                'caption',
                'name',
                'family',
                'specialty_title',
                'photo',
            ]);

        if ($relatedTo !== null) {
            if ($relatedTo->specialty_id === null && $relatedTo->city_id === null) {
                return collect();
            }

            $query
                ->where('id', '!=', $relatedTo->id)
                ->where(function ($relatedQuery) use ($relatedTo): void {
                    if ($relatedTo->specialty_id !== null) {
                        $relatedQuery->where('specialty_id', $relatedTo->specialty_id);
                    }

                    if ($relatedTo->city_id !== null) {
                        $method = $relatedTo->specialty_id !== null ? 'orWhere' : 'where';
                        $relatedQuery->{$method}('city_id', $relatedTo->city_id);
                    }
                })
                ->orderByRaw('CASE WHEN specialty_id = ? THEN 0 ELSE 1 END', [
                    $relatedTo->specialty_id ?? -1,
                ])
                ->orderByRaw('CASE WHEN city_id = ? THEN 0 ELSE 1 END', [
                    $relatedTo->city_id ?? -1,
                ])
                ->orderBy('id');
        } else {
            $query->orderBy('id');
        }

        $doctors = $query->get()->map(function (Doctor $doctor) use ($relatedTo): array {
            $clinics = $doctor->address
                ->map(function ($clinic): array {
                    $slot = $clinic->timeSlots->first();
                    $latestSlot = $clinic->latestPubliclyBookableTimeSlot;

                    return [
                        'id' => $clinic->id,
                        'name' => $clinic->name,
                        'full_address' => $clinic->full_address,
                        'city_name' => $clinic->city?->name,
                        'next_available' => $slot ? [
                            'date' => $slot->date->format('Y-m-d'),
                            'start_time' => substr($slot->start_time, 0, 5),
                        ] : null,
                        'latest_available' => $latestSlot ? [
                            'date' => $latestSlot->date->format('Y-m-d'),
                            'start_time' => substr($latestSlot->start_time, 0, 5),
                            'available_at' => ($latestSlot->updated_at ?? $latestSlot->created_at)?->toIso8601String(),
                        ] : null,
                    ];
                })
                ->filter(fn (array $clinic): bool => $clinic['next_available'] !== null)
                ->sortBy(fn (array $clinic): string => $clinic['next_available']['date'] . ' ' . $clinic['next_available']['start_time'])
                ->values();

            $nextClinic = $clinics->first();
            $latestClinic = $clinics
                ->filter(fn (array $clinic): bool => $clinic['latest_available'] !== null)
                ->sortByDesc(fn (array $clinic): string => $clinic['latest_available']['available_at'] ?? '')
                ->first();

            return [
                'id' => $doctor->id,
                'slug' => $doctor->slug,
                'caption' => $doctor->caption,
                'name' => $doctor->name,
                'family' => $doctor->family,
                'specialty_title' => $doctor->specialty_title,
                'photo' => $doctor->photo,
                'clinics' => $clinics->all(),
                'next_available' => $nextClinic['next_available'] ?? null,
                'next_clinic' => $nextClinic ? [
                    'id' => $nextClinic['id'],
                    'name' => $nextClinic['name'],
                    'full_address' => $nextClinic['full_address'],
                    'city_name' => $nextClinic['city_name'],
                ] : null,
                'latest_available' => $latestClinic['latest_available'] ?? null,
                'latest_clinic' => $latestClinic ? [
                    'id' => $latestClinic['id'],
                    'name' => $latestClinic['name'],
                    'full_address' => $latestClinic['full_address'],
                    'city_name' => $latestClinic['city_name'],
                ] : null,
                'match_reason' => $relatedTo === null
                    ? null
                    : ($relatedTo->specialty_id !== null && $doctor->specialty_id === $relatedTo->specialty_id
                        ? 'تخصص مشابه'
                        : 'در همان شهر'),
            ];
        });

        return $doctors
            ->sortBy(fn (array $doctor): string => $doctor['next_available']['date'] . ' ' . $doctor['next_available']['start_time'])
            ->when($limit !== null, fn (Collection $collection): Collection => $collection->take($limit))
            ->values();
    }
}
