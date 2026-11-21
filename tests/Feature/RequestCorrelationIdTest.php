<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class RequestCorrelationIdTest extends TestCase
{
    public function test_health_response_includes_a_generated_request_id(): void
    {
        $response = $this->get('/up');

        $response->assertOk();
        $this->assertTrue(
            \Illuminate\Support\Str::isUuid((string) $response->headers->get('X-Request-ID'))
        );
    }

    public function test_valid_incoming_request_id_is_preserved(): void
    {
        $requestId = '2c4e9d20-49a4-4e66-95f2-c44f8b72a10d';

        $this->withHeader('X-Request-ID', $requestId)
            ->get('/up')
            ->assertOk()
            ->assertHeader('X-Request-ID', $requestId);
    }

    public function test_invalid_incoming_request_id_is_replaced(): void
    {
        $response = $this->withHeader('X-Request-ID', 'not-a-uuid')->get('/up');

        $response->assertOk();
        $this->assertTrue(
            \Illuminate\Support\Str::isUuid((string) $response->headers->get('X-Request-ID'))
        );
        $this->assertNotSame('not-a-uuid', $response->headers->get('X-Request-ID'));
    }
}
