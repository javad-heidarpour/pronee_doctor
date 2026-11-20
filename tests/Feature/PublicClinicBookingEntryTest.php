<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PublicClinicBookingEntryTest extends TestCase
{
    use RefreshDatabase;

    public function test_clinic_specific_booking_link_preselects_only_a_bookable_clinic_of_that_doctor(): void
    {
        $doctor = $this->createDoctor();
        $clinicId = $this->createBookableClinic($doctor);

        $this->get(route('booking', [
            'slug' => $doctor->slug,
            'clinic' => $clinicId,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HomePage/Home1/Booking')
                ->where('clinicId', $clinicId)
                ->where('requestedClinicUnavailable', false));
    }

    public function test_booking_link_does_not_select_a_clinic_belonging_to_another_doctor(): void
    {
        $doctor = $this->createDoctor();
        $otherDoctor = $this->createDoctor();
        $otherClinicId = $this->createBookableClinic($otherDoctor);

        $this->get(route('booking', [
            'slug' => $doctor->slug,
            'clinic' => $otherClinicId,
        ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('HomePage/Home1/Booking')
                ->where('clinicId', null)
                ->where('requestedClinicUnavailable', true));
    }

    private function createDoctor(): Doctor
    {
        $unique = Str::lower(Str::random(10));

        return Doctor::factory()->create([
            'name' => 'پزشک ' . $unique,
            'family' => 'آزمایشی',
            'slug' => 'doctor-' . $unique,
        ]);
    }

    private function createBookableClinic(Doctor $doctor): int
    {
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'مطب آزمایشی',
            'full_address' => 'نشانی آزمایشی',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('time_slots')->insert([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $clinicId;
    }
}
