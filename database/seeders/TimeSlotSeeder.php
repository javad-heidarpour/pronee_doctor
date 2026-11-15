<?php
// database/seeders/TimeSlotSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\TimeSlot;
use Carbon\Carbon;

class TimeSlotSeeder extends Seeder
{
    public function run(): void
    {
        // دریافت پزشکان به همراه آدرس‌هایشان
        $doctors = Doctor::with('address')->get();

        if ($doctors->isEmpty()) {
            $this->command->warn('هیچ پزشکی در دیتابیس وجود ندارد. ابتدا پزشکان را seed کنید.');
            return;
        }

        foreach ($doctors as $doctor) {
            // اگر پزشک آدرسی نداشت، یک آدرس پیش‌فرض بساز
            if ($doctor->address->isEmpty()) {
                $this->command->warn("پزشک {$doctor->name} آدرسی ندارد. در حال ساخت آدرس پیش‌فرض...");
                
                \App\Models\Address::create([
                    'doctor_id' => $doctor->id,
                    'city_id' => 1, // فرض بر این است که شهر با id=1 وجود دارد
                    'name' => 'مطب اصلی',
                    'full_address' => 'تهران، خیابان نمونه، پلاک ۱',
                    'phone' => '02112345678',
                ]);
                
                // بارگذاری مجدد رابطه
                $doctor->load('address');
            }

            foreach ($doctor->address as $clinic) {
                $this->command->info("در حال ایجاد نوبت برای {$doctor->name} در {$clinic->name}");

                // تولید نوبت برای ۷ روز آینده
                for ($i = 0; $i < 7; $i++) {
                    $date = Carbon::now()->addDays($i);
                    
                    // روزهای جمعه تعطیل
                    if ($date->isFriday()) continue;

                    // تولید تایم از ۱۶:۰۰ تا ۲۰:۰۰ (هر ۳۰ دقیقه)
                    $currentTime = Carbon::parse('16:00');
                    $endTime = Carbon::parse('20:00');

                    while ($currentTime->lt($endTime)) {
                        TimeSlot::create([
                            'doctor_id' => $doctor->id,
                            'clinic_id' => $clinic->id,
                            'date' => $date->format('Y-m-d'),
                            'start_time' => $currentTime->format('H:i:s'),
                            'end_time' => $currentTime->copy()->addMinutes(30)->format('H:i:s'),
                            'status' => 'available',
                        ]);
                        
                        $currentTime->addMinutes(30);
                    }
                }
            }
        }
        
        $this->command->info('✅ تایم‌های نوبت‌دهی با موفقیت ایجاد شدند.');
    }
}