<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Home\BookAppointmentAction;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Mockery;
use Tests\TestCase;

final class PublicAppointmentPaymentHandoffTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_booking_returns_a_temporary_signed_payment_handoff(): void
    {
        $doctor = Doctor::factory()->create(['family' => 'آزمایشی']);
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'مطب آزمایشی',
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
            'status' => 'available',
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

        $appointment = \App\Models\Appointment::query()->findOrFail($appointmentId);
        $action = Mockery::mock(BookAppointmentAction::class);
        $action->shouldReceive('handle')->once()->andReturn($appointment);
        $this->app->instance(BookAppointmentAction::class, $action);

        $this->from(route('home'))->post(route('appointments.book'), [
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
        ])->assertRedirect(route('home'))
            ->assertSessionHas('payment_url');

        $paymentUrl = (string) session('payment_url');

        $this->assertStringContainsString(
            route('payment.pay', ['visit_number' => $appointmentId], false),
            $paymentUrl
        );
        $this->assertTrue(URL::hasValidSignature(Request::create($paymentUrl, 'POST')));
        $this->assertStringContainsString('expires=', $paymentUrl);
    }

    public function test_payment_start_rejects_unsigned_appointment_identifiers(): void
    {
        $this->post(route('payment.pay', ['visit_number' => 999999]))
            ->assertForbidden();
    }
}
