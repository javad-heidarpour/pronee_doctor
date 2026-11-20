<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\WorkingHours;

use App\Actions\Portal\WorkingHours\ToggleWorkingHourAction;
use App\Models\Doctor;
use App\Models\DoctorWorkingHour;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ToggleWorkingHourActionTest extends TestCase
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

    public function test_it_does_not_reactivate_an_overlapping_schedule(): void
    {
        $doctor = $this->doctor();
        DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 1,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);
        $inactive = DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 2,
            'day_of_week' => 1,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'duration_minutes' => 30,
            'is_active' => false,
        ]);

        $result = app(ToggleWorkingHourAction::class)->handle($inactive);

        $this->assertFalse($result);
        $this->assertFalse($inactive->fresh()->is_active);
    }

    public function test_it_can_deactivate_an_active_schedule(): void
    {
        $doctor = $this->doctor();
        $active = DoctorWorkingHour::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 1,
            'day_of_week' => 2,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->assertTrue(app(ToggleWorkingHourAction::class)->handle($active));
        $this->assertFalse($active->fresh()->is_active);
    }

    private function doctor(): Doctor
    {
        return Doctor::query()->create([
            'name' => 'Working Hour Test Doctor',
            'slug' => 'working-hour-test-' . uniqid(),
        ]);
    }
}
