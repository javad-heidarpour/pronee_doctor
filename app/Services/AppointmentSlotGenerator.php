<?php

namespace App\Services;

use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use App\Models\TimeSlot;
use Carbon\Carbon;

class AppointmentSlotGenerator
{
    /**
     * تولید نوبت‌های قابل رزرو برای ۷ روز آینده بر اساس ساعات کاری پزشک
     */
    public function generateForDoctor(Doctor $doctor, int $days = 7): int
    {
        $createdCount = 0;
        $startDate = Carbon::now()->startOfDay();
        $endDate = Carbon::now()->addDays($days)->endOfDay();

        // دریافت ساعات کاری پزشک برای تمام مطب‌ها
        $workingHours = DoctorWorkingHour::where('doctor_id', $doctor->id)
            ->where('is_active', true)
            ->get();

        // بررسی هر روز در بازه ۷ روزه
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            
            // تبدیل روز هفته Carbon به فرمت ما (0=شنبه)
            // Carbon: 0=یکشنبه، 1=دوشنبه، ... 6=شنبه
            $carbonDayOfWeek = $date->dayOfWeek;
            $ourDayOfWeek = ($carbonDayOfWeek + 1) % 7;

            // پیدا کردن ساعات کاری این روز خاص
            $daySchedules = $workingHours->filter(fn($wh) => $wh->day_of_week === $ourDayOfWeek);

            foreach ($daySchedules as $schedule) {
                // تولید نوبت‌ها بر اساس مدت ویزیت
                $currentTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->start_time);
                $endTime = Carbon::parse($date->format('Y-m-d') . ' ' . $schedule->end_time);

                while ($currentTime->lt($endTime)) {
                    $slotEnd = $currentTime->copy()->addMinutes($schedule->duration_minutes);

                    // جلوگیری از ایجاد نوبت تکراری
                    $exists = TimeSlot::where('doctor_id', $doctor->id)
                        ->where('clinic_id', $schedule->clinic_id)
                        ->where('date', $date->format('Y-m-d'))
                        ->where('start_time', $currentTime->format('H:i:s'))
                        ->exists();

                    if (!$exists) {
                        TimeSlot::create([
                            'doctor_id' => $doctor->id,
                            'clinic_id' => $schedule->clinic_id,
                            'date' => $date->format('Y-m-d'),
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $slotEnd->format('H:i:s'),
                            'status' => 'available',
                        ]);
                        $createdCount++;
                    }

                    $currentTime = $slotEnd;
                }
            }
        }

        return $createdCount;
    }

    /**
     * تولید نوبت برای تمام پزشکان (برای Cron Job)
     */
    public function generateForAllDoctors(int $days = 7): void
    {
        Doctor::where('is_active', true)->each(function ($doctor) use ($days) {
            $this->generateForDoctor($doctor, $days);
        });
    }
}