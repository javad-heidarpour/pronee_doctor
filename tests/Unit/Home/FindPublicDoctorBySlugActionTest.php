<?php

declare(strict_types=1);

namespace Tests\Unit\Home;

use App\Actions\Home\FindPublicDoctorBySlugAction;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class FindPublicDoctorBySlugActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_doctor_lookup_returns_only_whitelisted_doctor_attributes(): void
    {
        $doctor = Doctor::factory()->create([
            'family' => 'آزمایشی',
            'email' => 'private-doctor@example.test',
            'phone' => '09120000000',
            'Medical_system_number' => 'PRIVATE-123',
        ]);

        $publicDoctor = app(FindPublicDoctorBySlugAction::class)->handle($doctor->slug);
        $payload = $publicDoctor->toArray();

        $this->assertSame($doctor->slug, $payload['slug']);
        $this->assertSame($doctor->name, $payload['name']);
        $this->assertArrayNotHasKey('email', $payload);
        $this->assertArrayNotHasKey('phone', $payload);
        $this->assertArrayNotHasKey('Medical_system_number', $payload);
        $this->assertArrayNotHasKey('password', $payload);
        $this->assertArrayNotHasKey('user_id', $payload);
    }
}
