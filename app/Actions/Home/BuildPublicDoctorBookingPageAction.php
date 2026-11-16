<?php

namespace App\Actions\Home;

use App\Models\Appointment;
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
            ->map(function ($clinic) use ($doctor, $today, $oneHourLater) {
                $slots = $doctor->timeSlots()
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
                    'clinic' => $clinic,
                    'dates' => $slots
                        ->groupBy('date')
                        ->map(function ($daySlots) {
                            return [
                                'date' => $daySlots->first()->date->format('Y-m-d'),
                                'slots' => $daySlots->map(function ($slot) {
                                    $booked = Appointment::where(
                                        'time_slot_id',
                                        $slot->id
                                    )
                                        ->whereIn('status', ['pending', 'confirmed'])
                                        ->exists();

                                    return [
                                        'id' => $slot->id,
                                        'start_time' => substr($slot->start_time, 0, 5),
                                        'status' => $booked ? 'booked' : 'available',
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
