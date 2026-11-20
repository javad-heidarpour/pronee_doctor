<?php

namespace Tests\Feature\Visit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class VisitDetailsSecurityTest extends TestCase
{
    public function test_visit_details_reject_unsigned_public_requests(): void
    {
        $this->get('/visit/123')->assertForbidden();
    }

    public function test_payment_initiation_rejects_unsigned_public_requests(): void
    {
        $this->post('/payment/123/pay')->assertForbidden();
    }

    public function test_public_cancellation_rejects_unsigned_requests(): void
    {
        $this->post('/visit/123/cancel', ['patient_phone' => '09120000000'])->assertForbidden();
    }

    public function test_public_rescheduling_rejects_unsigned_requests(): void
    {
        $this->post('/visit/123/reschedule', [
            'patient_phone' => '09120000000',
            'new_time_slot_id' => 456,
        ])->assertForbidden();
    }

    public function test_temporary_signed_payment_url_is_valid_for_post(): void
    {
        $url = URL::temporarySignedRoute(
            'payment.pay',
            now()->addMinutes(5),
            ['visit_number' => 123]
        );

        $request = Request::create($url, 'POST');

        $this->assertTrue(URL::hasValidSignature($request));
    }

    public function test_temporary_signed_visit_url_is_valid(): void
    {
        $url = URL::temporarySignedRoute(
            'visit.show',
            now()->addMinutes(5),
            ['visit_number' => 123]
        );

        $request = Request::create($url, 'GET');

        $this->assertTrue(URL::hasValidSignature($request));
    }

    public function test_temporary_signed_cancellation_url_is_valid_for_post(): void
    {
        $url = URL::temporarySignedRoute(
            'visit.cancel',
            now()->addMinutes(5),
            ['visit_number' => 123]
        );

        $request = Request::create($url, 'POST');

        $this->assertTrue(URL::hasValidSignature($request));
    }

    public function test_temporary_signed_rescheduling_url_is_valid_for_post(): void
    {
        $url = URL::temporarySignedRoute(
            'visit.reschedule',
            now()->addMinutes(5),
            ['visit_number' => 123]
        );

        $request = Request::create($url, 'POST');

        $this->assertTrue(URL::hasValidSignature($request));
    }
}
