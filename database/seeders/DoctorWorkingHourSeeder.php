<?php
// database/seeders/DoctorWorkingHourSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;

class DoctorWorkingHourSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::with('address')->get();

        foreach ($doctors as $doctor) {
            foreach ($doctor->address as $clinic) {
                // شنبه تا چهارشنبه: ۱۶ تا ۲۰
                for ($day = 0; $day <= 4; $day++) {
                    DoctorWorkingHour::create([
                        'doctor_id' => $doctor->id,
                        'clinic_id' => $clinic->id,
                        'day_of_week' => $day,
                        'start_time' => '16:00:00',
                        'end_time' => '20:00:00',
                        'duration_minutes' => 30,
                        'is_active' => true,
                    ]);
                }

                // پنجشنبه: ۱۰ تا ۱۳
                DoctorWorkingHour::create([
                    'doctor_id' => $doctor->id,
                    'clinic_id' => $clinic->id,
                    'day_of_week' => 5,
                    'start_time' => '10:00:00',
                    'end_time' => '13:00:00',
                    'duration_minutes' => 30,
                    'is_active' => true,
                ]);

                // جمعه تعطیل (رکوردی ساخته نمی‌شود)
            }
        }
    }
}