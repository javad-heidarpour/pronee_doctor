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
}
