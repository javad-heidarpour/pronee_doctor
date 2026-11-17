<?php

namespace Tests\Feature\Auth;

use App\Models\User;

class RegistrationTest extends AuthTestCase
{
    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_public_registration_creates_a_least_privilege_user_account(): void
    {
        $response = $this->post('/register', [
            'name' => 'Test User',
            'family' => 'Tester',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated('web');
        $response->assertRedirect(route('home', absolute: false));

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertSame('user', $user->role);
        $this->assertSame('Tester', $user->family);
        $this->assertNotEmpty($user->username);
        $this->assertTrue($user->is_active);
        $this->assertFalse($user->isAdmin());
        $this->assertFalse($user->isDoctor());
        $this->assertFalse($user->isSecretary());
    }
}
