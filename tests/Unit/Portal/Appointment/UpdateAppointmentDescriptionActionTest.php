<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Appointment;

use App\Actions\Portal\Appointment\UpdateAppointmentDescriptionAction;
use App\DTOs\Portal\Appointment\UpdateAppointmentDescriptionData;
use App\Models\Appointment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class UpdateAppointmentDescriptionActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->string('status');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('surgery_type_id')->nullable();
            $table->date('surgery_date')->nullable();
            $table->timestamps();
        });

        Schema::create('surgery_types', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->unique();
            $table->timestamps();
        });
    }

    public function test_it_updates_visit_details_and_marks_the_appointment_as_visited(): void
    {
        $appointment = Appointment::query()->create([
            'status' => 'confirmed',
            'description' => 'Old description',
        ]);

        $updated = app(UpdateAppointmentDescriptionAction::class)->handle(
            $appointment,
            UpdateAppointmentDescriptionData::fromValidated([
                'description' => 'Visit summary',
                'new_surgery_type' => 'Test procedure',
                'surgery_date' => '2026-12-01',
            ])
        );

        $this->assertTrue($updated);
        $this->assertDatabaseHas('surgery_types', [
            'title' => 'Test procedure',
        ]);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'description' => 'Visit summary',
            'status' => 'visited',
            'surgery_date' => '2026-12-01',
        ]);
    }

    public function test_it_does_not_modify_a_cancelled_appointment(): void
    {
        $appointment = Appointment::query()->create([
            'status' => 'cancelled',
            'description' => 'Keep this description',
        ]);

        $updated = app(UpdateAppointmentDescriptionAction::class)->handle(
            $appointment,
            UpdateAppointmentDescriptionData::fromValidated([
                'description' => 'Should not be saved',
            ])
        );

        $this->assertFalse($updated);
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'description' => 'Keep this description',
            'status' => 'cancelled',
        ]);
    }
}
