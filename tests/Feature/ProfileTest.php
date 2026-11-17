<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\Feature\Auth\AuthTestCase;

class ProfileTest extends AuthTestCase
{
    public function test_guest_is_redirected_from_the_portal_profile(): void
    {
        $this->get('/portal/profile')
            ->assertRedirect(route('portal.login'));
    }

    public function test_public_user_account_cannot_access_doctor_profile_management(): void
    {
        $publicUser = User::factory()->create();

        $this->actingAs($publicUser, 'web')
            ->get('/portal/profile')
            ->assertRedirect(route('portal.login'));

        $this->assertGuest('web');
    }
}
