<?php

declare(strict_types=1);

namespace App\Services\ClinicBrain;

use App\Models\Appointment;
use App\Models\TimeSlot;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class ClinicBrainAnalyzer
{
    public function analyze(int $doctorId, array $clinicIds, ?int $selectedClinicId = null): array
    {
        $clinicIds = $selectedClinicId !== null
            ? array_values(array_intersect($clinicIds, [$selectedClinicId]))
            : $clinicIds;

        if ($selectedClinicId !== null && $clinicIds === []) {
            abort(403, 'دسترسی به شعبه انتخاب‌شده مجاز نیست.');
        }

        if ($clinicIds === []) {
            return $this->emptyResult();
        }

        $today = CarbonImmutable::today();
        $historyStart = $today->subDays(55);
        $history = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('appointment_date', [$historyStart->toDateString(), $today->toDateString()])
            ->get(['id', 'clinic_id', 'appointment_date', 'status']);

        $total = $history->count();
        $visited = $history->where('status', 'visited')->count();
        $cancelled = $history->where('status', 'cancelled')->count();
        $explicitNoShows = $history->whereIn('status', ['no_show', 'missed'])->count();
        $weekdayDemand = $history
            ->filter(fn (Appointment $item) => $item->appointment_date !== null)
            ->groupBy(fn (Appointment $item) => CarbonImmutable::parse($item->appointment_date)->dayOfWeekIso)
            ->map(fn (Collection $items) => round($items->count() / 8, 1));

        $slots = TimeSlot::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('clinic_id', $clinicIds)
            ->whereBetween('date', [$today->toDateString(), $today->addDays(13)->toDateString()])
            ->where('status', '!=', 'cancelled')
            ->with(['clinic:id,name', 'appointment:id,time_slot_id,status'])
            ->orderBy('date')->orderBy('start_time')->get();

        $isBooked = fn (TimeSlot $slot): bool =>
            $slot->status === 'booked'
            || in_array($slot->appointment?->status, ['pending', 'confirmed'], true);

        $days = collect(range(0, 13))->map(function (int $offset) use ($today, $slots, $weekdayDemand, $isBooked): array {
            $date = $today->addDays($offset);
            $daySlots = $slots->filter(fn (TimeSlot $slot) => CarbonImmutable::parse($slot->date)->toDateString() === $date->toDateString());
            $capacity = $daySlots->count();
            $booked = $daySlots->filter($isBooked)->count();
            $expected = (float) ($weekdayDemand[$date->dayOfWeekIso] ?? 0);

            return [
                'date' => $date->toDateString(),
                'capacity' => $capacity,
                'booked' => $booked,
                'available' => max(0, $capacity - $booked),
                'expected_demand' => $expected,
                'demand_pressure' => $capacity > 0 ? round(($expected / $capacity) * 100) : null,
            ];
        });

        $availableQuery = $slots->filter(fn (TimeSlot $slot) =>
            $slot->status === 'available'
            && !in_array($slot->appointment?->status, ['pending', 'confirmed'], true)
        );
        $availableCount = $availableQuery->count();
        $available = $availableQuery->take(12)->map(fn (TimeSlot $slot) => [
            'id' => $slot->id,
            'clinic_id' => $slot->clinic_id,
            'clinic_name' => $slot->clinic?->name,
            'date' => CarbonImmutable::parse($slot->date)->toDateString(),
            'start_time' => substr((string) $slot->start_time, 0, 5),
            'end_time' => substr((string) $slot->end_time, 0, 5),
        ])->values();

        $cancelRate = $total > 0 ? round(($cancelled / $total) * 100, 1) : null;
        $insights = [];

        if ($total < 20) {
            $insights[] = [
                'type' => 'info', 'title' => 'داده تاریخی هنوز محدود است',
                'message' => 'برای تحلیل قابل اتکاتر، حداقل ۲۰ نوبت ثبت‌شده در بازه ۸ هفته‌ای لازم است.',
                'action' => 'فعلاً از آمار توصیفی استفاده کنید؛ از نتیجه‌گیری پیش‌بینانه خودداری می‌شود.',
            ];
        }

        foreach ($days->filter(fn (array $day) => $day['demand_pressure'] !== null && $day['demand_pressure'] >= 85)->take(3) as $day) {
            $insights[] = [
                'type' => 'warning', 'title' => 'احتمال فشار ظرفیت',
                'message' => 'تقاضای تاریخی این روز نسبت به ظرفیت ثبت‌شده بالا است: ' . $day['date'],
                'action' => 'ظرفیت پزشک یا ساعات کاری این روز را بازبینی کنید.',
            ];
        }

        foreach ($days->filter(fn (array $day) => $day['capacity'] >= 3 && $day['booked'] / max(1, $day['capacity']) < 0.25)->take(2) as $day) {
            $insights[] = [
                'type' => 'opportunity', 'title' => 'ظرفیت خالی قابل استفاده',
                'message' => $day['date'] . ' هنوز ' . $day['available'] . ' جای خالی از ' . $day['capacity'] . ' ظرفیت ثبت‌شده دارد.',
                'action' => 'در صورت وجود بیمار منتظر، زمان‌های آزاد را برای پیشنهاد نوبت بررسی کنید.',
            ];
        }

        if ($total >= 20 && $cancelRate !== null && $cancelRate >= 20) {
            $insights[] = [
                'type' => 'warning', 'title' => 'نرخ لغو قابل توجه',
                'message' => 'در بازه ۸ هفته‌ای ' . $cancelRate . '٪ از نوبت‌ها لغو شده‌اند.',
                'action' => 'علت لغوها را ثبت و زمان‌بندی پیامک یادآوری را ارزیابی کنید.',
            ];
        }

        if ($insights === []) {
            $insights[] = [
                'type' => 'success', 'title' => 'وضعیت عملیاتی پایدار',
                'message' => 'در داده‌های فعلی هشدار ظرفیت برجسته‌ای شناسایی نشد.',
                'action' => 'روند ظرفیت و لغو نوبت‌ها را به‌صورت دوره‌ای بررسی کنید.',
            ];
        }

        return [
            'period' => ['from' => $historyStart->toDateString(), 'to' => $today->toDateString(), 'days' => 56, 'appointments' => $total],
            'metrics' => [
                'historical_appointments' => $total,
                'visited' => $visited,
                'cancelled' => $cancelled,
                'cancellation_rate' => $cancelRate,
                'no_show_rate' => $total >= 20 && $explicitNoShows > 0 ? round(($explicitNoShows / $total) * 100, 1) : null,
                'no_show_sample' => $explicitNoShows,
                'upcoming_capacity' => $slots->count(),
                'upcoming_booked' => $slots->filter($isBooked)->count(),
                'upcoming_available' => $availableCount,
            ],
            'days' => $days->values(),
            'available_slots' => $available,
            'insights' => $insights,
            'methodology' => [
                'تقاضا: میانگین نوبت‌های ثبت‌شده در همان روز هفته طی ۸ هفته گذشته؛ برآورد توصیفی، نه پیش‌بینی قطعی.',
                'ظرفیت: فقط اسلات‌های ثبت‌شده در جدول زمان‌بندی؛ ساعات کاری فاقد اسلات لحاظ نمی‌شوند.',
                'عدم مراجعه: فقط وضعیت صریح no_show یا missed محاسبه می‌شود؛ لغو یا نوبت تأییدنشده عدم مراجعه فرض نمی‌شود.',
            ],
        ];
    }

    private function emptyResult(): array
    {
        return [
            'period' => ['from' => null, 'to' => null, 'days' => 56, 'appointments' => 0],
            'metrics' => [
                'historical_appointments' => 0, 'visited' => 0, 'cancelled' => 0,
                'cancellation_rate' => null, 'no_show_rate' => null, 'no_show_sample' => 0,
                'upcoming_capacity' => 0, 'upcoming_booked' => 0, 'upcoming_available' => 0,
            ],
            'days' => [], 'available_slots' => [],
            'insights' => [[
                'type' => 'info', 'title' => 'هنوز شعبه فعالی در دسترس نیست',
                'message' => 'برای فعال‌شدن تحلیل، یک شعبه فعال و زمان‌بندی آن را ثبت کنید.',
                'action' => 'ابتدا شعبه و ساعات کاری را تکمیل کنید.',
            ]],
            'methodology' => [],
        ];
    }
}
