<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Shifts;

use App\Actions\Portal\Shifts\GenerateTimeSlotsAction;
use App\Actions\Portal\Shifts\ToggleTimeSlotAction;
use App\Actions\Portal\WorkingHours\DeleteDoctorHolidayAction;
use App\DTOs\Portal\Scheduling\GenerateTimeSlotsData;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\DoctorWorkingHour;
use App\Models\TimeSlot;
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
            $table->string('cancelled_reason', 24)->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->string('status')->default('pending');
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
    public function test_generation_does_not_reopen_a_manually_cancelled_slot(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Manual Cancellation Doctor',
            'slug' => 'manual-cancel-' . uniqid(),
            'is_active' => true,
        ]);
        $clinicId = (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'Manual Cancellation Clinic',
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
        $slot = TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => '2026-11-20',
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => 'available',
        ]);

        $this->assertSame('cancelled', app(ToggleTimeSlotAction::class)->handle($slot));
        $this->assertSame('manual', $slot->fresh()->cancelled_reason);

        $result = app(GenerateTimeSlotsAction::class)->handle(
            $doctor,
            new GenerateTimeSlotsData(1, $clinicId)
        );

        $this->assertSame(1, $result['generated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertSame('cancelled', $slot->fresh()->status);
        $this->assertDatabaseHas('time_slots', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => '2026-11-20',
            'start_time' => '09:30:00',
            'status' => 'available',
        ]);
    }

    public function test_generation_recreates_holiday_closed_slots_only_after_the_holiday_is_removed(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Holiday Reopen Doctor',
            'slug' => 'holiday-reopen-' . uniqid(),
            'is_active' => true,
        ]);
        $clinicId = (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'Holiday Reopen Clinic',
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
        $holiday = DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-20',
            'title' => 'تعطیلی',
        ]);
        TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => '2026-11-20',
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => 'cancelled',
            'cancelled_reason' => 'holiday',
        ]);
        TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => '2026-11-20',
            'start_time' => '09:30:00',
            'end_time' => '10:00:00',
            'status' => 'cancelled',
            'cancelled_reason' => 'holiday',
        ]);

        $duringHoliday = app(GenerateTimeSlotsAction::class)->handle(
            $doctor,
            new GenerateTimeSlotsData(1, $clinicId)
        );
        $this->assertSame(0, $duringHoliday['generated']);

        app(DeleteDoctorHolidayAction::class)->handle($doctor, $holiday);

        $afterHoliday = app(GenerateTimeSlotsAction::class)->handle(
            $doctor,
            new GenerateTimeSlotsData(1, $clinicId)
        );

        $this->assertSame(2, $afterHoliday['generated']);
        $this->assertDatabaseHas('time_slots', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => '2026-11-20',
            'start_time' => '09:00:00',
            'status' => 'available',
        ]);
    }

    public function test_generation_is_idempotent_for_already_generated_slots(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Idempotent Generator Doctor',
            'slug' => 'idempotent-generator-' . uniqid(),
            'is_active' => true,
        ]);
        $clinicId = (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'Idempotent Generator Clinic',
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

        $action = app(GenerateTimeSlotsAction::class);
        $data = new GenerateTimeSlotsData(1, $clinicId);
        $first = $action->handle($doctor, $data);
        $second = $action->handle($doctor, $data);

        $this->assertSame(2, $first['generated']);
        $this->assertSame(0, $second['generated']);
        $this->assertSame(2, $second['skipped']);
        $this->assertDatabaseCount('time_slots', 2);
    }

}
