<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Portal\Profile\UpdateDoctorHonorBadgesAction;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpdateDoctorHonorBadgesTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_honor_badges_are_replaced_with_the_submitted_public_list(): void
    {
        $doctor = Doctor::factory()->create();

        $doctor->honorBadges()->create([
            'title' => 'سابقه قدیمی',
            'icon' => '★',
            'sort_order' => 1,
        ]);

        app(UpdateDoctorHonorBadgesAction::class)->handle($doctor, [
            ['title' => 'عضو انجمن تخصصی', 'icon' => '🏅'],
            ['title' => 'دارای بورد تخصصی', 'icon' => '🎓'],
        ]);

        $this->assertDatabaseMissing('honor_badges', [
            'doctor_id' => $doctor->id,
            'title' => 'سابقه قدیمی',
        ]);

        $this->assertDatabaseHas('honor_badges', [
            'doctor_id' => $doctor->id,
            'title' => 'عضو انجمن تخصصی',
            'icon' => '🏅',
            'sort_order' => 1,
        ]);

        $this->assertDatabaseHas('honor_badges', [
            'doctor_id' => $doctor->id,
            'title' => 'دارای بورد تخصصی',
            'icon' => '🎓',
            'sort_order' => 2,
        ]);

        $this->assertSame(2, $doctor->honorBadges()->count());
    }

    public function test_empty_honor_badge_list_removes_existing_public_badges(): void
    {
        $doctor = Doctor::factory()->create();
        $doctor->honorBadges()->create([
            'title' => 'سابقه قبلی',
            'icon' => '🏅',
            'sort_order' => 1,
        ]);

        app(UpdateDoctorHonorBadgesAction::class)->handle($doctor, []);

        $this->assertSame(0, $doctor->honorBadges()->count());
    }
}
