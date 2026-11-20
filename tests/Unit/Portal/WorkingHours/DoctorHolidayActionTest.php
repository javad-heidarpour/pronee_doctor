<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\WorkingHours;

use App\Actions\Portal\WorkingHours\CreateDoctorHolidayAction;
use App\Actions\Portal\WorkingHours\DeleteDoctorHolidayAction;
use App\Actions\Portal\Shifts\CreateTimeSlotAction;
use App\Actions\Portal\Shifts\UpdateTimeSlotAction;
use App\Actions\Portal\Shifts\ToggleTimeSlotAction;
use App\DTOs\Portal\Scheduling\TimeSlotData;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class DoctorHolidayActionTest extends TestCase
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

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
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
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('status');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->timestamps();
        });
    }

    public function test_creating_a_holiday_closes_all_unbooked_slots_for_that_clinic_and_date(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $available = $this->slot($doctor, $clinicId, '2026-11-25', 'available');
        $alreadyCancelled = $this->slot($doctor, $clinicId, '2026-11-25', 'cancelled');

        $holiday = app(CreateDoctorHolidayAction::class)->handle($doctor, [
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-25',
            'title' => 'تعطیلی رسمی',
            'note' => 'مطب تعطیل است',
        ], null);

        $this->assertInstanceOf(DoctorHoliday::class, $holiday);
        $this->assertSame('cancelled', $available->fresh()->status);
        $this->assertSame('holiday', $available->fresh()->cancelled_reason);
        $this->assertSame('cancelled', $alreadyCancelled->fresh()->status);
        $this->assertDatabaseHas('doctor_holidays', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-25',
        ]);
    }

    public function test_holiday_cannot_be_created_when_the_clinic_has_an_active_appointment(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $slot = $this->slot($doctor, $clinicId, '2026-11-25', 'booked');

        DB::table('appointments')->insert([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'status' => 'confirmed',
            'appointment_date' => '2026-11-25',
            'appointment_time' => '09:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            app(CreateDoctorHolidayAction::class)->handle($doctor, [
                'clinic_id' => $clinicId,
                'holiday_date' => '2026-11-25',
                'title' => 'تعطیلی',
            ], null);
            $this->fail('A clinic holiday must not invalidate an active patient appointment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('holiday_date', $exception->errors());
        }

        $this->assertDatabaseCount('doctor_holidays', 0);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_duplicate_holiday_is_rejected_without_duplicate_records(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        app(CreateDoctorHolidayAction::class)->handle($doctor, [
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-25',
            'title' => 'تعطیلی اول',
        ], null);

        try {
            app(CreateDoctorHolidayAction::class)->handle($doctor, [
                'clinic_id' => $clinicId,
                'holiday_date' => '2026-11-25',
                'title' => 'تعطیلی تکراری',
            ], null);
            $this->fail('Duplicate clinic holiday must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('holiday_date', $exception->errors());
        }

        $this->assertDatabaseCount('doctor_holidays', 1);
    }

    public function test_manual_slot_creation_is_rejected_on_a_holiday(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-25',
            'title' => 'تعطیلی',
        ]);

        $created = app(CreateTimeSlotAction::class)->handle(
            $doctor,
            new TimeSlotData($clinicId, '2026-11-25', '09:00:00', '09:30:00')
        );

        $this->assertFalse($created);
        $this->assertDatabaseCount('time_slots', 0);
    }

    public function test_existing_slot_cannot_be_moved_into_a_holiday(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $slot = $this->slot($doctor, $clinicId, '2026-11-24', 'available');
        DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-25',
            'title' => 'تعطیلی',
        ]);

        $updated = app(UpdateTimeSlotAction::class)->handle(
            $doctor,
            $slot,
            new TimeSlotData($clinicId, '2026-11-25', '10:00:00', '10:30:00')
        );

        $this->assertFalse($updated);
        $this->assertSame('2026-11-24', $slot->fresh()->date->format('Y-m-d'));
    }

    public function test_a_cancelled_slot_cannot_be_reactivated_on_a_holiday(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $slot = $this->slot($doctor, $clinicId, '2026-11-25', 'cancelled');
        DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-25',
            'title' => 'تعطیلی',
        ]);

        $result = app(ToggleTimeSlotAction::class)->handle($slot);

        $this->assertNull($result);
        $this->assertSame('cancelled', $slot->fresh()->status);
    }

    public function test_deleting_a_holiday_removes_only_the_exception_record(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $holiday = DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'holiday_date' => '2026-11-25',
            'title' => 'تعطیلی',
        ]);
        $slot = $this->slot($doctor, $clinicId, '2026-11-25', 'cancelled');

        app(DeleteDoctorHolidayAction::class)->handle($doctor, $holiday);

        $this->assertDatabaseMissing('doctor_holidays', ['id' => $holiday->id]);
        // A deleted holiday does not silently re-open slots; generation is explicit.
        $this->assertSame('cancelled', $slot->fresh()->status);
    }

    private function doctorAndClinic(): array
    {
        $doctor = Doctor::query()->create([
            'name' => 'Holiday Test Doctor',
            'slug' => 'holiday-test-' . uniqid(),
            'is_active' => true,
        ]);

        $clinicId = (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'Holiday Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$doctor, $clinicId];
    }

    private function slot(Doctor $doctor, int $clinicId, string $date, string $status): TimeSlot
    {
        return TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => $date,
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => $status,
        ]);
    }
}
