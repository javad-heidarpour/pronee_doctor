<?php

namespace App\Actions\Home;

use App\Models\Doctor;
use App\Models\VisitReason;

class BuildPublicDoctorBookingPageAction
{
    public function handle(Doctor $doctor): array
    {
        $today = now()->toDateString();
        $oneHourLater = now()->addHour();

        $visitReasons = VisitReason::query()
            ->select('id', 'title')
            ->orderBy('title')
            ->get();

        $clinicsWithSlots = $doctor->address
            ->where('is_active', true)
            ->map(function ($clinic) use ($doctor, $today, $oneHourLater) {
                $slots = $doctor->timeSlots()
                    ->with([
                        'appointment' => fn ($query) => $query
                            ->whereIn('status', ['pending', 'confirmed']),
                    ])
                    ->where('clinic_id', $clinic->id)
                    ->whereIn('status', ['available', 'booked'])
                    ->where(function ($query) use ($today, $oneHourLater) {
                        $query
                            ->where('date', '>', $today)
                            ->orWhere(function ($q) use ($today, $oneHourLater) {
                                $q
                                    ->where('date', $today)
                                    ->where(
                                        'start_time',
                                        '>=',
                                        $oneHourLater->format('H:i:s')
                                    );
                            });
                    })
                    ->orderBy('date')
                    ->orderBy('start_time')
                    ->get();

                return [
                    'clinic' => [
                        'id' => $clinic->id,
                        'name' => $clinic->name,
                        'full_address' => $clinic->full_address,
                        'city' => $clinic->city?->name,
                        'phone' => $clinic->phone,
                        'Latitude' => $clinic->Latitude,
                        'Longitude' => $clinic->Longitude,
                    ],
                    'dates' => $slots
                        ->groupBy('date')
                        ->map(function ($daySlots) {
                            return [
                                'date' => $daySlots->first()->date->format('Y-m-d'),
                                'slots' => $daySlots->map(function ($slot) {
                                    return [
                                        'id' => $slot->id,
                                        'start_time' => substr($slot->start_time, 0, 5),
                                        'status' => $slot->appointment ? 'booked' : 'available',
                                    ];
                                })->values(),
                            ];
                        })
                        ->values(),
                ];
            })
            ->filter(function ($clinic) {
                return $clinic['dates']->isNotEmpty();
            })
            ->values();

        return [
            'clinicsWithSlots' => $clinicsWithSlots,
            'visitReasons' => $visitReasons,
        ];
    }
}
