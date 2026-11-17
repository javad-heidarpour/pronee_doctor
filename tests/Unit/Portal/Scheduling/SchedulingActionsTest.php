<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Scheduling;

use App\Actions\Portal\Shifts\CreateTimeSlotAction;
use App\Actions\Portal\Shifts\GenerateTimeSlotsAction;
use App\Actions\Portal\Shifts\UpdateTimeSlotAction;
use App\Actions\Portal\WorkingHours\SaveWorkingHourAction;
use App\DTOs\Portal\Scheduling\TimeSlotData;
use App\DTOs\Portal\Scheduling\WorkingHourData;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class SchedulingActionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
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

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('doctor_working_hours', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('duration_minutes');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_create_time_slot_rejects_overlapping_ranges_for_the_same_doctor(): void
    {
        $doctor = $this->doctor();
        $this->clinic($doctor);
        $this->clinic($doctor);

        $action = app(CreateTimeSlotAction::class);
        $data = TimeSlotData::fromValidated([
            'clinic_id' => 1,
            'date' => '2026-11-18',
            'start_time' => '09:00',
            'end_time' => '09:30',
        ]);

        $this->assertTrue($action->handle($doctor, $data));
        $this->assertFalse($action->handle($doctor, TimeSlotData::fromValidated([
            'clinic_id' => 2,
            'date' => '2026-11-18',
            'start_time' => '09:15',
            'end_time' => '09:45',
        ])));

        $this->assertDatabaseCount('time_slots', 1);
    }

    public function test_create_time_slot_rejects_a_clinic_owned_by_another_doctor(): void
    {
        $doctor = $this->doctor();
        $foreignDoctor = $this->doctor();
        $foreignClinic = $this->clinic($foreignDoctor);

        $this->expectException(HttpException::class);
        $this->expectExceptionCode(403);

        app(CreateTimeSlotAction::class)->handle($doctor, TimeSlotData::fromValidated([
            'clinic_id' => $foreignClinic->id,
            'date' => '2026-11-18',
            'start_time' => '09:00',
            'end_time' => '09:30',
        ]));
    }

    public function test_update_time_slot_ignores_its_own_range_but_rejects_another_overlap(): void
    {
        $doctor = $this->doctor();
        $this->clinic($doctor);
        $slot = $this->slot($doctor->id, '09:00', '09:30');
        $this->slot($doctor->id, '10:00', '10:30');

        $action = app(UpdateTimeSlotAction::class);

        $this->assertFalse($action->handle($doctor, $slot, TimeSlotData::fromValidated([
            'clinic_id' => 1,
            'date' => '2026-11-18',
            'start_time' => '09:15',
            'end_time' => '10:15',
        ])));

        $this->assertSame('09:00', substr((string) $slot->fresh()->start_time, 0, 5));

        $this->assertTrue($action->handle($doctor, $slot, TimeSlotData::fromValidated([
            'clinic_id' => 1,
            'date' => '2026-11-18',
            'start_time' => '11:00',
            'end_time' => '11:30',
        ])));
    }

    public function test_generate_slots_skips_existing_overlap_and_creates_remaining_slots(): void
    {
        Carbon::setTestNow('2026-11-14 08:00:00');

        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);

        DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->slot($doctor->id, '09:00', '09:30', '2026-11-14');

        $result = app(GenerateTimeSlotsAction::class)->handle($doctor, 1, $clinic->id);

        $this->assertSame(1, $result['generated']);
        $this->assertSame(1, $result['skipped']);
        $this->assertTrue($result['has_working_hours']);
        $this->assertDatabaseCount('time_slots', 2);
    }

    public function test_generate_slots_ignores_working_hours_for_inactive_clinics(): void
    {
        Carbon::setTestNow('2026-11-14 08:00:00');

        $doctor = $this->doctor();
        $inactiveClinic = $this->clinic($doctor, active: false);

        DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $inactiveClinic->id,
            'day_of_week' => 0,
            'start_time' => '09:00',
            'end_time' => '10:00',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $result = app(GenerateTimeSlotsAction::class)->handle($doctor, 1);

        $this->assertFalse($result['has_working_hours']);
        $this->assertSame(0, $result['generated']);
        $this->assertDatabaseCount('time_slots', 0);
    }

    public function test_save_working_hour_rejects_overlap_across_clinics_and_allows_self_update(): void
    {
        $doctor = $this->doctor();
        $clinicOne = $this->clinic($doctor);
        $clinicTwo = $this->clinic($doctor);

        $workingHour = DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicOne->id,
            'day_of_week' => 2,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $action = app(SaveWorkingHourAction::class);

        $conflict = $action->handle($doctor, WorkingHourData::fromValidated([
            'clinic_id' => $clinicTwo->id,
            'day_of_week' => 2,
            'duration_minutes' => 30,
            'start_time' => '11:00',
            'end_time' => '13:00',
        ]));

        $this->assertFalse($conflict['saved']);
        $this->assertSame($workingHour->id, $conflict['duplicate']->id);

        $updated = $action->handle($doctor, WorkingHourData::fromValidated([
            'clinic_id' => $clinicOne->id,
            'day_of_week' => 2,
            'duration_minutes' => 20,
            'start_time' => '08:00',
            'end_time' => '11:00',
        ]), $workingHour);

        $this->assertTrue($updated['saved']);
        $this->assertSame('08:00', substr((string) $workingHour->fresh()->start_time, 0, 5));
    }

    private function clinic(Doctor $doctor, bool $active = true): Address
    {
        return Address::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Test clinic',
            'is_active' => $active,
        ]);
    }

    private function doctor(): Doctor
    {
        return Doctor::query()->create([
            'name' => 'Scheduling Test Doctor',
            'slug' => 'scheduling-test-doctor',
        ]);
    }

    private function slot(
        int $doctorId,
        string $start,
        string $end,
        string $date = '2026-11-18',
        string $status = 'available'
    ): TimeSlot {
        return TimeSlot::query()->create([
            'doctor_id' => $doctorId,
            'clinic_id' => 1,
            'date' => $date,
            'start_time' => $start,
            'end_time' => $end,
            'status' => $status,
        ]);
    }
}
