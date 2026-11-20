<?php

declare(strict_types=1);

namespace Tests\Feature\Visit;

use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PublicVisitStatusTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_visit_page_shows_current_status_without_exposing_patient_phone(): void
    {
        $doctor = Doctor::factory()->create([
            'name' => 'دکتر نمونه',
            'family' => 'پزشکی',
            'slug' => 'sample-public-doctor',
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'مطب مرکزی',
            'full_address' => 'نشانی آزمایشی',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slotId = DB::table('time_slots')->insertGetId([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'booked',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
            'status' => 'pending',
            'appointment_date' => now()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'fee_rial' => 7000000,
            'reservation_expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'visit.show',
            now()->addMinutes(15),
            ['visit_number' => $appointmentId]
        );

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Visit/Show')
                ->where('visit.doctor_slug', 'sample-public-doctor')
                ->where('visit.doctor_name', 'دکتر نمونه پزشکی')
                ->where('visit.appointment_status', 'pending')
                ->where('visit.appointment_status_label', 'در انتظار')
                ->where('visit.payment_status', 'unpaid')
                ->where('visit.clinic.name', 'مطب مرکزی')
                ->missing('visit.patient_phone'));
    }
}
