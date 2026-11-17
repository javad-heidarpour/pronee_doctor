<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Models\User;
use Tests\TestCase;

final class AuthenticationConfigurationTest extends TestCase
{
    public function test_all_application_accounts_use_the_shared_web_guard(): void
    {
        $this->assertSame('web', config('auth.defaults.guard'));
        $this->assertSame('session', config('auth.guards.web.driver'));
        $this->assertSame(User::class, config('auth.providers.users.model'));
    }

    public function test_legacy_doctor_authentication_provider_is_not_configured(): void
    {
        $this->assertArrayNotHasKey('doctor', config('auth.guards'));
        $this->assertArrayNotHasKey('doctors', config('auth.providers'));
        $this->assertArrayNotHasKey('doctors', config('auth.passwords'));
    }
}
