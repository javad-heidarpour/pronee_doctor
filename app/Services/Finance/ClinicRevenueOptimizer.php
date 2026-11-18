<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\TimeSlot;
use Illuminate\Support\Carbon;

class ClinicRevenueOptimizer
{
    /**
     * Analyze only persisted appointment, slot and successful-payment records.
     * Appointment KPIs use appointment_date; cash collection uses paid_at.
     */
    public function analyze(int $doctorId, array $clinicIds, string $from, string $to, ?int $clinicId = null): array
    {
        $scope = $clinicId ? [$clinicId] : $clinicIds;
        if ($scope === []) {
            return $this->summarize(0, 0, [], 0, 0);
        }

        $slots = TimeSlot::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('clinic_id', $scope)
            ->whereBetween('date', [$from, $to]);

        $totalSlots = (clone $slots)->count();
        $occupiedSlots = (clone $slots)
            ->where(function ($query): void {
                $query->where('status', 'booked')
                    ->orWhereHas('appointment', fn ($appointments) => $appointments->whereIn('status', ['pending', 'confirmed', 'visited']));
            })->count();

        $statusRows = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->whereIn('clinic_id', $scope)
            ->whereBetween('appointment_date', [$from, $to])
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->get();

        $statusCounts = $statusRows->mapWithKeys(
            fn ($row) => [(string) $row->status => (int) $row->aggregate]
        )->all();

        $payments = AppointmentPayment::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [
                Carbon::parse($from)->startOfDay(),
                Carbon::parse($to)->endOfDay(),
            ])
            ->whereHas('appointment', function ($query) use ($doctorId, $scope): void {
                $query->where('doctor_id', $doctorId)->whereIn('clinic_id', $scope);
            });

        return $this->summarize(
            $totalSlots,
            $occupiedSlots,
            $statusCounts,
            (int) (clone $payments)->sum('amount_rial'),
            (int) (clone $payments)->distinct('appointment_id')->count('appointment_id'),
        );
    }

    /**
     * Pure, independently testable KPI and recommendation calculation.
     */
    public function summarize(int $totalSlots, int $occupiedSlots, array $statuses, int $paidRevenue, int $paidAppointments): array
    {
        $totalAppointments = array_sum(array_map('intval', $statuses));
        $cancelled = (int) ($statuses['cancelled'] ?? 0);
        $visited = (int) ($statuses['visited'] ?? 0);
        $active = (int) ($statuses['pending'] ?? 0) + (int) ($statuses['confirmed'] ?? 0);
        $availableSlots = max(0, $totalSlots - $occupiedSlots);

        $utilization = $totalSlots > 0
            ? round(min(100, ($occupiedSlots / $totalSlots) * 100), 1)
            : null;
        $cancellationRate = $totalAppointments > 0
            ? round(($cancelled / $totalAppointments) * 100, 1)
            : null;
        $averagePaidAmount = $paidAppointments > 0
            ? (int) round($paidRevenue / $paidAppointments)
            : null;

        $recommendations = [];
        if ($totalSlots === 0) {
            $recommendations[] = [
                'level' => 'info',
                'title' => 'ظرفیت قابل ارزیابی ثبت نشده است',
                'description' => 'برای این بازه، ساعت کاری یا نوبت قابل رزروی در تقویم ثبت نشده؛ ابتدا برنامه نوبت‌دهی شعبه را تکمیل کنید.',
            ];
        } elseif ($utilization < 50) {
            $recommendations[] = [
                'level' => 'warning',
                'title' => 'بخشی از ظرفیت شعبه بلااستفاده است',
                'description' => 'زمان‌های کم‌تقاضا را بررسی و در صورت امکان با اطلاع‌رسانی مجاز و یادآوری نوبت، دسترسی بیماران به این زمان‌ها را بهتر کنید.',
            ];
        } elseif ($utilization >= 85) {
            $recommendations[] = [
                'level' => 'success',
                'title' => 'استفاده از ظرفیت بالاست',
                'description' => 'برای جلوگیری از فشار کاری، کیفیت خدمت و تأخیرها را پایش کنید و فقط در صورت وجود نیروی انسانی و امکانات کافی ظرفیت جدید تعریف کنید.',
            ];
        }

        if ($cancellationRate !== null && $cancellationRate >= 15) {
            $recommendations[] = [
                'level' => 'warning',
                'title' => 'نرخ لغو نوبت قابل توجه است',
                'description' => 'نرخ لغو به ۱۵٪ یا بیشتر رسیده است؛ این فقط آستانه عملیاتی پیشنهادی برای بازبینی است، نه معیار بالینی یا استاندارد قطعی. علت لغوها و فاصله تا موعد مراجعه را بررسی کنید.',
            ];
        }

        if ($availableSlots > 0) {
            $recommendations[] = [
                'level' => 'info',
                'title' => 'ظرفیت خالی برای بازیابی نوبت وجود دارد',
                'description' => 'زمان‌های آزاد را با فهرست انتظار و ترجیحات ثبت‌شده بیماران تطبیق دهید؛ پیشنهاد زمان به بیمار به معنی رزرو خودکار نیست.',
            ];
        }

        if ($paidAppointments === 0) {
            $recommendations[] = [
                'level' => 'info',
                'title' => 'دریافت موفقی در بازه مالی ثبت نشده است',
                'description' => 'وضعیت درگاه و ثبت دریافت‌های حضوری را بررسی کنید. مبلغ صفر به معنی رایگان بودن خدمات یا نبود درآمد قطعی نیست.',
            ];
        }

        return [
            'capacity' => [
                'total_slots' => $totalSlots,
                'occupied_slots' => $occupiedSlots,
                'available_slots' => $availableSlots,
                'utilization_rate' => $utilization,
                'data_status' => $totalSlots > 0 ? 'available' : 'insufficient_data',
            ],
            'appointments' => [
                'total' => $totalAppointments,
                'visited' => $visited,
                'active' => $active,
                'cancelled' => $cancelled,
                'cancellation_rate' => $cancellationRate,
                'data_status' => $totalAppointments > 0 ? 'available' : 'insufficient_data',
            ],
            'revenue' => [
                'paid_amount' => $paidRevenue,
                'paid_appointments' => $paidAppointments,
                'average_paid_amount' => $averagePaidAmount,
                'data_status' => $paidAppointments > 0 ? 'available' : 'insufficient_data',
                'basis' => 'successful_payments_by_paid_at',
            ],
            'recommendations' => $recommendations,
            'limitations' => [
                'no_show' => 'در مدل فعلی وضعیت مستقل عدم مراجعه ثبت نشده است.',
                'profit' => 'هزینه‌های سربار، سهم پزشک و هزینه مستقیم هر خدمت برای محاسبه سود خالص به‌صورت کامل و یکپارچه در این گزارش قابل اتکا نیست.',
                'lost_revenue' => 'درآمد ازدست‌رفته محاسبه نشده؛ ظرفیت خالی به‌تنهایی معادل درآمد قطعی ازدست‌رفته نیست.',
            ],
        ];
    }
}
