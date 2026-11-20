<?php

declare(strict_types=1);

namespace App\Actions\Home;

use App\Models\Doctor;
use App\Models\VisitReason;

final class BuildPublicDoctorBookingPageAction
{
    public function handle(Doctor $doctor): array
    {
        $visitReasons = VisitReason::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $clinicsWithSlots = $doctor->address()
            ->where('is_active', true)
            ->with('city:id,name')
            ->get()
            ->map(function ($clinic) use ($doctor) {
                $slots = $doctor->timeSlots()
                    ->publiclyBookable()
                    ->where('clinic_id', $clinic->id)
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->get();

                return [
                    'clinic' => [
                        'id' => $clinic->id,
                        'name' => $clinic->name,
                        'full_address' => $clinic->full_address,
                        'city' => $clinic->city?->only(['id', 'name']),
                        'phone' => $clinic->phone,
                        'Latitude' => $clinic->Latitude,
                        'Longitude' => $clinic->Longitude,
                    ],
                    'dates' => $slots
                        ->groupBy(fn ($slot) => $slot->date->format('Y-m-d'))
                        ->map(function ($daySlots) {
                            return [
                                'date' => $daySlots->first()->date->format('Y-m-d'),
                                'slots' => $daySlots->map(fn ($slot) => [
                                    'id' => $slot->id,
                                    'start_time' => substr($slot->start_time, 0, 5),
                                    'status' => 'available',
                                ])->values(),
                            ];
                        })
                        ->values(),
                ];
            })
            ->filter(fn ($clinic) => $clinic['dates']->isNotEmpty())
            ->values();

        return [
            'clinicsWithSlots' => $clinicsWithSlots,
            'visitReasons' => $visitReasons,
        ];
    }
}
