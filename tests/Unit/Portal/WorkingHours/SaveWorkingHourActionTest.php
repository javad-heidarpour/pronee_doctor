<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\WorkingHours;

use App\Actions\Portal\WorkingHours\SaveWorkingHourAction;
use App\DTOs\Portal\Scheduling\WorkingHourData;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class SaveWorkingHourActionTest extends TestCase
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
    }

    public function test_it_creates_an_active_working_hour_for_an_owned_active_clinic(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();

        $result = app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($clinicId, '09:00', '12:00')
        );

        $this->assertTrue($result['saved']);
        $this->assertNull($result['duplicate']);
        $this->assertDatabaseHas('doctor_working_hours', [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
            'is_active' => true,
        ]);
    }

    public function test_it_rejects_overlapping_active_hours_even_across_two_clinics(): void
    {
        [$doctor, $firstClinicId] = $this->doctorAndClinic();
        $secondClinicId = $this->clinic($doctor);

        app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($firstClinicId, '09:00', '12:00')
        );

        $result = app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($secondClinicId, '11:00', '13:00')
        );

        $this->assertFalse($result['saved']);
        $this->assertSame($firstClinicId, (int) $result['duplicate']->clinic_id);
        $this->assertDatabaseCount('doctor_working_hours', 1);
    }

    public function test_adjacent_active_working_hours_are_allowed(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();

        $first = app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($clinicId, '09:00', '10:00')
        );
        $second = app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($clinicId, '10:00', '11:00')
        );

        $this->assertTrue($first['saved']);
        $this->assertTrue($second['saved']);
        $this->assertDatabaseCount('doctor_working_hours', 2);
    }

    public function test_updating_an_active_working_hour_excludes_itself_from_conflict_detection(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $hour = $this->workingHour($doctor, $clinicId, '09:00', '10:00');

        $result = app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($clinicId, '10:00', '11:00'),
            $hour
        );

        $this->assertTrue($result['saved']);
        $this->assertSame('10:00:00', $hour->fresh()->start_time);
        $this->assertSame('11:00:00', $hour->fresh()->end_time);
    }

    public function test_updating_an_active_working_hour_does_not_overwrite_an_overlapping_schedule(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $hour = $this->workingHour($doctor, $clinicId, '09:00', '10:00');
        $other = $this->workingHour($doctor, $clinicId, '11:00', '12:00');

        $result = app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($clinicId, '10:30', '11:30'),
            $hour
        );

        $this->assertFalse($result['saved']);
        $this->assertSame($other->id, $result['duplicate']->id);
        $this->assertSame('09:00:00', $hour->fresh()->start_time);
        $this->assertSame('10:00:00', $hour->fresh()->end_time);
    }

    public function test_editing_an_inactive_working_hour_keeps_it_inactive(): void
    {
        [$doctor, $clinicId] = $this->doctorAndClinic();
        $hour = $this->workingHour($doctor, $clinicId, '09:00', '10:00', false);

        $result = app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($clinicId, '13:00', '14:00'),
            $hour
        );

        $this->assertTrue($result['saved']);
        $this->assertFalse($hour->fresh()->is_active);
        $this->assertSame('13:00:00', $hour->fresh()->start_time);
    }

    public function test_it_rejects_a_clinic_not_owned_by_the_doctor(): void
    {
        [$doctor] = $this->doctorAndClinic();
        [$anotherDoctor, $otherClinicId] = $this->doctorAndClinic();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        app(SaveWorkingHourAction::class)->handle(
            $doctor,
            $this->data($otherClinicId, '09:00', '10:00')
        );
    }

    private function doctorAndClinic(): array
    {
        $doctor = Doctor::query()->create([
            'name' => 'Schedule Test Doctor',
            'slug' => 'schedule-test-' . uniqid(),
        ]);

        return [$doctor, $this->clinic($doctor)];
    }

    private function clinic(Doctor $doctor): int
    {
        return (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function data(int $clinicId, string $start, string $end): WorkingHourData
    {
        return new WorkingHourData($clinicId, 1, 30, $start, $end);
    }

    private function workingHour(
        Doctor $doctor,
        int $clinicId,
        string $start,
        string $end,
        bool $active = true
    ): DoctorWorkingHour {
        return DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'day_of_week' => 1,
            'start_time' => $start,
            'end_time' => $end,
            'duration_minutes' => 30,
            'is_active' => $active,
        ]);
    }
}
