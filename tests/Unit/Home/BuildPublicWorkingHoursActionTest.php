<?php

declare(strict_types=1);

namespace Tests\Unit\Home;

use App\Actions\Home\BuildPublicWorkingHoursAction;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\DoctorWorkingHour;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BuildPublicWorkingHoursActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctor_working_hours', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctor_holidays', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('holiday_date');
            $table->string('title', 160);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['doctor_id', 'clinic_id', 'holiday_date']);
        });
    }

    public function test_public_hours_include_each_clinic_and_its_upcoming_holidays(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Multi Clinic Doctor',
            'slug' => 'multi-clinic-' . uniqid(),
            'is_active' => true,
        ]);

        $cityId = (int) DB::table('cities')->insertGetId([
            'name' => 'Bojnourd',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $firstClinicId = $this->clinic($doctor, $cityId, 'Clinic One');
        $secondClinicId = $this->clinic($doctor, $cityId, 'Clinic Two');

        foreach ([[$firstClinicId, '09:00:00'], [$secondClinicId, '14:00:00']] as [$clinicId, $start]) {
            DoctorWorkingHour::query()->create([
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinicId,
                'day_of_week' => 1,
                'start_time' => $start,
                'end_time' => '17:00:00',
                'duration_minutes' => 30,
                'is_active' => true,
            ]);
        }

        DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $secondClinicId,
            'holiday_date' => now()->addDays(4)->toDateString(),
            'title' => 'تعطیلی شعبه دوم',
        ]);

        $result = app(BuildPublicWorkingHoursAction::class)->handle($doctor);

        $this->assertCount(2, $result['clinics']);
        $this->assertSame('Clinic One', $result['clinics'][0]['clinic']);
        $this->assertSame('Clinic Two', $result['clinics'][1]['clinic']);
        $this->assertSame('۰۹:۰۰ - ۱۷:۰۰', $result['clinics'][0]['days'][1]['hours']);
        $this->assertSame('۱۴:۰۰ - ۱۷:۰۰', $result['clinics'][1]['days'][1]['hours']);
        $this->assertCount(1, $result['holidays']);
        $this->assertSame('Clinic Two', $result['holidays'][0]['clinic']);
    }

    private function clinic(Doctor $doctor, int $cityId, string $name): int
    {
        return (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'city_id' => $cityId,
            'name' => $name,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
