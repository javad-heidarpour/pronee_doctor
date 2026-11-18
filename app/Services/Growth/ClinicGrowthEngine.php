<?php

declare(strict_types=1);

namespace App\Services\Growth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

final class ClinicGrowthEngine
{
    /**
     * Aggregate-only growth analytics. No patient names, phones or clinical details
     * are returned to this dashboard.
     */
    public function analyze(int $doctorId, array $clinicIds, string $from, string $to, ?int $clinicId = null): array
    {
        $scope = $clinicId !== null ? [$clinicId] : $clinicIds;
        if ($scope === []) {
            return $this->emptyReport($from, $to);
        }

        $appointments = DB::table('appointments')
            ->where('doctor_id', $doctorId)
            ->whereIn('clinic_id', $scope)
            ->whereBetween('appointment_date', [$from, $to]);

        $statuses = (clone $appointments)->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')->pluck('total', 'status')->map(fn ($n) => (int) $n)->all();

        $activePatients = (clone $appointments)->whereNotIn('status', ['cancelled', 'failed'])
            ->whereNotNull('patient_id')->distinct()->count('patient_id');

        $firstVisits = DB::table('appointments')
            ->select('patient_id')->selectRaw('MIN(appointment_date) as first_visit')
            ->where('doctor_id', $doctorId)->whereIn('clinic_id', $scope)
            ->whereNotNull('patient_id')->whereNotIn('status', ['cancelled', 'failed'])
            ->groupBy('patient_id');
        $newPatients = DB::query()->fromSub($firstVisits, 'first_visits')
            ->whereBetween('first_visit', [$from, $to])->count();
        $returningPatients = max(0, $activePatients - $newPatients);

        $lastVisits = DB::table('appointments')
            ->select('patient_id')->selectRaw('MAX(appointment_date) as last_visit')
            ->where('doctor_id', $doctorId)->whereIn('clinic_id', $scope)
            ->whereNotIn('status', ['cancelled', 'failed'])->whereNotNull('patient_id')
            ->groupBy('patient_id');
        $inactivePatients = DB::query()->fromSub($lastVisits, 'last_visits')
            ->whereDate('last_visit', '<', Carbon::parse($to)->subDays(90)->toDateString())
            ->count();

        $upcomingPatients = DB::table('appointments')
            ->where('doctor_id', $doctorId)->whereIn('clinic_id', $scope)
            ->whereDate('appointment_date', '>=', today()->toDateString())
            ->whereNotIn('status', ['cancelled', 'failed'])
            ->whereNotNull('patient_id')->distinct()->count('patient_id');

        $slots = DB::table('time_slots')->where('doctor_id', $doctorId)
            ->whereIn('clinic_id', $scope)->whereBetween('date', [$from, $to]);
        $totalSlots = (clone $slots)->count();
        $occupiedSlots = (clone $slots)->where(function ($q) {
            $q->where('status', 'booked')->orWhereExists(function ($sub) {
                $sub->selectRaw('1')->from('appointments')
                    ->whereColumn('appointments.time_slot_id', 'time_slots.id')
                    ->whereIn('appointments.status', ['pending', 'confirmed', 'visited']);
            });
        })->count();

        $revenue = DB::table('appointment_payments')
            ->join('appointments', 'appointments.id', '=', 'appointment_payments.appointment_id')
            ->where('appointments.doctor_id', $doctorId)->whereIn('appointments.clinic_id', $scope)
            ->where('appointment_payments.status', 'paid')
            ->whereBetween('appointment_payments.paid_at', [
                Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay(),
            ]);
        $paidRevenue = (int) (clone $revenue)->sum('appointment_payments.amount_rial');
        $paidCount = (int) (clone $revenue)->distinct('appointment_payments.appointment_id')
            ->count('appointment_payments.appointment_id');

        $campaigns = DB::table('clinic_marketing_campaigns as c')
            ->leftJoin('addresses as a', 'a.id', '=', 'c.clinic_id')
            ->where('c.doctor_id', $doctorId)
            ->where(fn ($q) => $q->whereNull('c.clinic_id')->orWhereIn('c.clinic_id', $scope))
            ->select('c.id', 'c.name', 'c.channel', 'c.status', 'c.budget_rial')
            ->selectSub(DB::table('clinic_marketing_events')->selectRaw('COUNT(*)')
                ->whereColumn('campaign_id', 'c.id')->where('event_type', 'click'), 'clicks')
            ->selectSub(DB::table('clinic_marketing_events')->selectRaw('COUNT(*)')
                ->whereColumn('campaign_id', 'c.id')->where('event_type', 'booking'), 'bookings')
            ->orderByDesc('c.created_at')->limit(8)->get()
            ->map(function ($campaign) {
                $campaign->clicks = (int) $campaign->clicks;
                $campaign->bookings = (int) $campaign->bookings;
                $campaign->conversion_rate = $campaign->clicks > 0
                    ? round($campaign->bookings * 100 / $campaign->clicks, 1) : null;
                $campaign->cost_per_booking = $campaign->bookings > 0 && $campaign->budget_rial !== null
                    ? (int) round((int) $campaign->budget_rial / $campaign->bookings) : null;
                return $campaign;
            });

        $campaignTotals = DB::table('clinic_marketing_events as e')
            ->join('clinic_marketing_campaigns as c', 'c.id', '=', 'e.campaign_id')
            ->where('c.doctor_id', $doctorId)
            ->where(fn ($q) => $q->whereNull('c.clinic_id')->orWhereIn('c.clinic_id', $scope))
            ->selectRaw("SUM(CASE WHEN e.event_type = 'click' THEN 1 ELSE 0 END) as clicks")
            ->selectRaw("SUM(CASE WHEN e.event_type = 'booking' THEN 1 ELSE 0 END) as bookings")
            ->first();
        $clicks = (int) ($campaignTotals->clicks ?? 0);
        $bookings = (int) ($campaignTotals->bookings ?? 0);
        $totalAppointments = array_sum($statuses);
        $cancelled = (int) ($statuses['cancelled'] ?? 0);
        $cancellationRate = $totalAppointments > 0 ? round($cancelled * 100 / $totalAppointments, 1) : null;
        $utilization = $totalSlots > 0 ? round(min(100, $occupiedSlots * 100 / $totalSlots), 1) : null;

        $opportunities = [];
        if ($inactivePatients > 0) {
            $opportunities[] = [
                'key' => 'retention', 'level' => 'warning', 'title' => 'فرصت بازبینی بیماران غیرفعال',
                'value' => $inactivePatients,
                'description' => 'این تعداد بیمار بیش از ۹۰ روز از آخرین مراجعه ثبت‌شده‌شان گذشته است. پیش از هر تماس، ضرورت پیگیری، رضایت ارتباطی و دستورالعمل مرکز را بررسی کنید.',
            ];
        }
        if ($totalSlots > 0 && $utilization < 60) {
            $opportunities[] = [
                'key' => 'capacity', 'level' => 'info', 'title' => 'ظرفیت خالی قابل بررسی',
                'value' => max(0, $totalSlots - $occupiedSlots),
                'description' => 'ظرفیت آزاد را با فهرست انتظار و برنامه واقعی نیروی انسانی تطبیق دهید؛ ظرفیت خالی معادل درآمد ازدست‌رفته قطعی نیست.',
            ];
        }
        if ($cancellationRate !== null && $cancellationRate >= 15) {
            $opportunities[] = [
                'key' => 'cancellation', 'level' => 'warning', 'title' => 'بازبینی علت لغو نوبت‌ها',
                'value' => $cancellationRate,
                'description' => 'نرخ لغو در این بازه حداقل ۱۵٪ است. علت‌ها، زمان اطلاع‌رسانی و امکان یادآوری مجاز را بررسی کنید.',
            ];
        }
        if ($clicks > 0 && $bookings === 0) {
            $opportunities[] = [
                'key' => 'campaign', 'level' => 'warning', 'title' => 'بازبینی مسیر تبدیل کمپین',
                'value' => $clicks,
                'description' => 'برای کمپین‌های نمایش‌داده‌شده کلیک ثبت شده اما نوبت منتسبی دیده نمی‌شود؛ لینک، صفحه رزرو و صحت ثبت رویداد را بررسی کنید.',
            ];
        }
        if ($newPatients > 0 && $returningPatients > 0) {
            $opportunities[] = [
                'key' => 'loyalty', 'level' => 'success', 'title' => 'پایه مناسب برای بهبود تداوم مراجعه',
                'value' => $returningPatients,
                'description' => 'بخشی از بیماران بازگشتی هستند. کیفیت تجربه، دسترسی و پیگیری‌های ضروری را بهبود دهید؛ از ایجاد مراجعه یا خدمت غیرضروری خودداری کنید.',
            ];
        }

        return [
            'period' => ['from' => $from, 'to' => $to],
            'patients' => [
                'active' => $activePatients, 'new' => $newPatients,
                'returning' => $returningPatients, 'inactive_over_90_days' => $inactivePatients,
                'upcoming' => $upcomingPatients,
            ],
            'appointments' => [
                'total' => $totalAppointments, 'visited' => (int) ($statuses['visited'] ?? 0),
                'cancelled' => $cancelled, 'pending' => (int) ($statuses['pending'] ?? 0),
                'cancellation_rate' => $cancellationRate,
            ],
            'capacity' => [
                'total' => $totalSlots, 'occupied' => $occupiedSlots,
                'available' => max(0, $totalSlots - $occupiedSlots), 'utilization_rate' => $utilization,
            ],
            'marketing' => [
                'campaigns' => $campaigns, 'clicks' => $clicks, 'bookings' => $bookings,
                'conversion_rate' => $clicks > 0 ? round($bookings * 100 / $clicks, 1) : null,
            ],
            'finance' => [
                'paid_revenue_rial' => $paidRevenue, 'paid_appointments' => $paidCount,
                'average_paid_rial' => $paidCount > 0 ? (int) round($paidRevenue / $paidCount) : null,
                'basis' => 'successful_payments_by_paid_at',
            ],
            'opportunities' => $opportunities,
            'limitations' => [
                'patient_metrics' => 'آمار بیماران بر اساس شناسه پرونده و مراجعه‌های ثبت‌شده است؛ بیمار فاقد شناسه پرونده در این بخش شمرده نمی‌شود.',
                'inactive' => 'غیرفعال بودن فقط بر مبنای فاصله از آخرین مراجعه است و احتمال ریزش یا نیاز پزشکی را اثبات نمی‌کند.',
                'marketing' => 'آمار کمپین‌ها تجمیعی و بر اساس رویدادهای ثبت‌شده است؛ انتساب درآمد قطعی به کمپین در این نسخه انجام نمی‌شود.',
                'profit' => 'درآمد نمایش‌داده‌شده دریافت موفق ناخالص است، نه سود خالص پس از هزینه‌ها.',
            ],
        ];
    }

    private function emptyReport(string $from, string $to): array
    {
        return [
            'period' => ['from' => $from, 'to' => $to],
            'patients' => ['active' => 0, 'new' => 0, 'returning' => 0, 'inactive_over_90_days' => 0, 'upcoming' => 0],
            'appointments' => ['total' => 0, 'visited' => 0, 'cancelled' => 0, 'pending' => 0, 'cancellation_rate' => null],
            'capacity' => ['total' => 0, 'occupied' => 0, 'available' => 0, 'utilization_rate' => null],
            'marketing' => ['campaigns' => [], 'clicks' => 0, 'bookings' => 0, 'conversion_rate' => null],
            'finance' => ['paid_revenue_rial' => 0, 'paid_appointments' => 0, 'average_paid_rial' => null, 'basis' => 'successful_payments_by_paid_at'],
            'opportunities' => [],
            'limitations' => ['data' => 'برای این حساب مطب قابل دسترسی ثبت نشده است.'],
        ];
    }
}
