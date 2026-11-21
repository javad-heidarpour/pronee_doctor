<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

final class AppointmentSatisfactionControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('appointment_satisfaction_surveys', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id')->unique();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedTinyInteger('overall_rating');
            $table->unsignedTinyInteger('communication_rating');
            $table->unsignedTinyInteger('waiting_rating');
            $table->unsignedTinyInteger('facility_rating');
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('appointment_satisfaction_surveys');
        Schema::dropIfExists('appointments');
        parent::tearDown();
    }

    public function test_signed_completed_appointment_can_submit_one_satisfaction_survey(): void
    {
        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 7,
            'status' => 'visited',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'patient.satisfaction.show',
            now()->addMinutes(15),
            ['appointment' => $appointmentId]
        );

        $this->get($url)->assertOk()->assertInertia(fn ($page) => $page
            ->component('Satisfaction/Show')
            ->where('submitted', false));

        $this->post($url, [
            'overall_rating' => 5,
            'communication_rating' => 4,
            'waiting_rating' => 3,
            'facility_rating' => 4,
            'comment' => 'تجربه خوبی بود.',
            'is_anonymous' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('appointment_satisfaction_surveys', [
            'appointment_id' => $appointmentId,
            'doctor_id' => 7,
            'overall_rating' => 5,
            'communication_rating' => 4,
            'waiting_rating' => 3,
            'facility_rating' => 4,
            'is_anonymous' => 1,
        ]);

        $this->post($url, [
            'overall_rating' => 5,
            'communication_rating' => 5,
            'waiting_rating' => 5,
            'facility_rating' => 5,
            'is_anonymous' => true,
        ])->assertStatus(409);

        $this->assertDatabaseCount('appointment_satisfaction_surveys', 1);
    }

    public function test_unsigned_or_expired_survey_links_are_rejected(): void
    {
        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 8,
            'status' => 'completed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get('/satisfaction/' . $appointmentId)->assertForbidden();

        $expired = URL::temporarySignedRoute(
            'patient.satisfaction.show',
            now()->subMinute(),
            ['appointment' => $appointmentId]
        );

        $this->get($expired)->assertForbidden();
    }

    public function test_non_completed_appointments_cannot_be_reviewed(): void
    {
        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 9,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'patient.satisfaction.show',
            now()->addMinutes(15),
            ['appointment' => $appointmentId]
        );

        $this->get($url)->assertNotFound();
        $this->post($url, [
            'overall_rating' => 5,
            'communication_rating' => 5,
            'waiting_rating' => 5,
            'facility_rating' => 5,
            'is_anonymous' => true,
        ])->assertNotFound();

        $this->assertDatabaseCount('appointment_satisfaction_surveys', 0);
    }

    public function test_rating_values_outside_one_to_five_are_rejected(): void
    {
        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => 10,
            'status' => 'visited',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'patient.satisfaction.show',
            now()->addMinutes(15),
            ['appointment' => $appointmentId]
        );

        $this->from($url)->post($url, [
            'overall_rating' => 6,
            'communication_rating' => 4,
            'waiting_rating' => 4,
            'facility_rating' => 4,
            'is_anonymous' => true,
        ])->assertSessionHasErrors(['overall_rating']);

        $this->assertDatabaseCount('appointment_satisfaction_surveys', 0);
    }
}
