<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_portal_profile(): void
    {
        $this->get('/portal/profile')
            ->assertRedirect(route('portal.login'));
    }

    public function test_public_user_account_cannot_access_doctor_profile_management(): void
    {
        $patient = User::factory()->create();

        $this->actingAs($patient, 'web')
            ->get('/portal/profile')
            ->assertRedirect(route('portal.login'));

        $this->assertGuest('web');
    }
}
