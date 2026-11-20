<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Shifts;

use App\Actions\Portal\Shifts\GenerateTimeSlotsAction;
use App\DTOs\Portal\Scheduling\GenerateTimeSlotsData;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\DoctorWorkingHour;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class GenerateTimeSlotsSkipsDoctorHolidaysTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-20 08:00:00');

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
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

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_slot_generation_skips_a_clinic_holiday(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Holiday Generator Doctor',
            'slug' => 'holiday-generator-' . uniqid(),
            'is_active' => true,
        ]);

        $clinicId = (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'Generator Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'day_of_week' => 6,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-20',
            'title' => 'تعطیلی روز جمعه',
        ]);

        $result = app(GenerateTimeSlotsAction::class)->handle(
            $doctor,
            new GenerateTimeSlotsData(1, $clinicId)
        );

        $this->assertTrue($result['has_working_hours']);
        $this->assertSame(0, $result['generated']);
        $this->assertSame(2, $result['skipped']);
        $this->assertDatabaseCount('time_slots', 0);
    }
}
