<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicAppointmentBookingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_appointment_success_endpoint_rejects_unsigned_numeric_ids(): void
    {
        $this->get(route('appointments.success', ['id' => 1]))
            ->assertForbidden();
    }

    public function test_persian_phone_digits_are_normalized_before_validation(): void
    {
        $response = $this->from('/')->post(route('appointments.book'), [
            'time_slot_id' => 999999,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '+۹۸ ۹۱۲-۳۴۵-۶۷۸۹',
        ]);

        $response->assertSessionHasErrors(['time_slot_id']);

        $errors = session('errors')->getBag('default')->getMessages();

        $this->assertArrayNotHasKey('patient_phone', $errors);
    }

    public function test_public_booking_validation_errors_are_in_persian(): void
    {
        $response = $this->from('/')->post(route('appointments.book'), [
            'time_slot_id' => 999999,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '123',
        ]);

        $response->assertSessionHasErrors(['time_slot_id', 'patient_phone']);

        $this->assertSame(
            'شماره همراه باید ۱۱ رقم و با ۰۹ شروع شود.',
            session('errors')->first('patient_phone')
        );
    }
}
