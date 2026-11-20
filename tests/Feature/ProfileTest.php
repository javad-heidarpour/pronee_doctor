<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
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

    public function test_doctor_profile_exposes_the_public_photo_url(): void
    {
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        $user = User::factory()->create(['role' => 'doctor']);

        $user->doctorProfile()->create([
            'photo' => 'doctors/profile-photo.jpg',
        ]);

        $this->actingAs($user, 'web')
            ->get('/portal/profile')
            ->assertInertia(fn (Assert $page) => $page
                ->component('Portal/Profile')
                ->where('doctor.photo', 'doctors/profile-photo.jpg')
                ->where('doctor.photo_url', asset('storage/doctors/profile-photo.jpg'))
            );
    }
}
