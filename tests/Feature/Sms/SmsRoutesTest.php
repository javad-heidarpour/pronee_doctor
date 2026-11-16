<?php

namespace Tests\Feature\Sms;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SmsRoutesTest extends TestCase
{
    public function test_visit_sms_endpoint_does_not_send_messages_on_get_requests(): void
    {
        $response = $this->getJson('/sms/visit/123');

        $response->assertMethodNotAllowed();
    }

    public function test_visit_sms_endpoint_accepts_post_requests(): void
    {
        $route = Route::getRoutes()->match(
            request()->create('/sms/visit/123', 'POST')
        );

        $this->assertSame('sms.visit', $route->getName());
        $this->assertContains('POST', $route->methods());
        $this->assertNotContains('GET', $route->methods());
    }

    public function test_visit_sms_requires_the_session_that_created_the_booking(): void
    {
        $response = $this->postJson('/sms/visit/123');

        $response->assertForbidden();
    }

    public function test_visit_sms_rejects_an_expired_booking_session(): void
    {
        $response = $this->withSession([
            'sms.visit.allowed.123' => now()->subMinute()->timestamp,
        ])->postJson('/sms/visit/123');

        $response->assertForbidden();
    }

    public function test_confirmation_sms_is_not_exposed_as_a_public_route(): void
    {
        $this->assertFalse(Route::has('sms.Confirmation'));
    }
}
