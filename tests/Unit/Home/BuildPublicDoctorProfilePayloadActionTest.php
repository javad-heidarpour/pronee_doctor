<?php

declare(strict_types=1);

namespace Tests\Unit\Home;

use App\Actions\Home\BuildPublicDoctorProfilePayloadAction;
use App\Models\Address;
use App\Models\City;
use App\Models\Doctor;
use App\Models\DoctorSocial;
use App\Models\HonorBadge;
use App\Models\SocialPlatform;
use Tests\TestCase;

final class BuildPublicDoctorProfilePayloadActionTest extends TestCase
{
    public function test_public_doctor_model_hides_internal_account_and_lifecycle_fields(): void
    {
        $doctor = new Doctor();
        $doctor->setRawAttributes([
            'id' => 8,
            'user_id' => 20,
            'email' => 'private@example.test',
            'phone' => '09120000000',
            'Medical_system_number' => 123456,
            'registration_status' => 'approved',
            'site_expires_at' => '2030-01-01 00:00:00',
            'approved_by' => 2,
        ], true);

        $serialized = $doctor->hidePrivatePublicFields()->toArray();

        foreach ([
            'user_id',
            'email',
            'phone',
            'Medical_system_number',
            'registration_status',
            'site_expires_at',
            'approved_by',
        ] as $privateField) {
            $this->assertArrayNotHasKey($privateField, $serialized);
        }

        $this->assertSame(8, $serialized['id']);
    }

    public function test_public_payload_contains_profile_content_without_private_account_fields(): void
    {
        $doctor = new Doctor();
        $doctor->setRawAttributes([
            'id' => 7,
            'user_id' => 19,
            'name' => 'پزشک',
            'family' => 'آزمایشی',
            'caption' => 'دکتر',
            'slug' => 'test-doctor',
            'email' => 'private@example.test',
            'phone' => '09120000000',
            'Medical_system_number' => 123456,
            'photo' => 'doctors/avatar.jpg',
            'description' => 'معرفی عمومی پزشک',
            'meta_title' => 'عنوان سئو',
            'meta_description' => 'توضیحات سئو',
            'registration_status' => 'approved',
            'site_expires_at' => '2030-01-01 00:00:00',
        ], true);

        $city = new City();
        $city->setRawAttributes(['id' => 3, 'name' => 'بجنورد', 'province' => 'خراسان شمالی'], true);
        $doctor->setRelation('city', $city);

        $specialty = new \App\Models\Specialty();
        $specialty->setRawAttributes(['id' => 4, 'name' => 'داخلی', 'slug' => 'internal', 'icon' => '✚'], true);
        $doctor->setRelation('specialty', $specialty);

        $clinic = new Address();
        $clinic->setRawAttributes([
            'id' => 11,
            'doctor_id' => 7,
            'organization_id' => 88,
            'name' => 'مطب مرکزی',
            'full_address' => 'بجنورد، خیابان اصلی',
            'city_id' => 3,
            'phone' => '05832220000',
            'Latitude' => '37.4742',
            'Longitude' => '57.3238',
        ], true);
        $clinic->setRelation('city', $city);
        $doctor->setRelation('address', collect([$clinic]));

        $platform = new SocialPlatform();
        $platform->setRawAttributes([
            'id' => 5,
            'name' => 'instagram',
            'title' => 'اینستاگرام',
            'base_url' => 'https://instagram.com/',
        ], true);

        $social = new DoctorSocial();
        $social->setRawAttributes([
            'id' => 6,
            'doctor_id' => 7,
            'social_platform_id' => 5,
            'username' => 'doctor.test',
            'url' => 'https://instagram.com/doctor.test',
            'is_visible' => true,
        ], true);
        $social->setRelation('platform', $platform);
        $doctor->setRelation('socials', collect([$social]));

        $badge = new HonorBadge();
        $badge->setRawAttributes([
            'id' => 9,
            'doctor_id' => 7,
            'title' => 'عضو انجمن تخصصی',
            'icon' => '🏅',
            'sort_order' => 1,
        ], true);
        $doctor->setRelation('honorBadges', collect([$badge]));

        $payload = app(BuildPublicDoctorProfilePayloadAction::class)->handle($doctor);

        $this->assertSame('test-doctor', $payload['slug']);
        $this->assertSame('معرفی عمومی پزشک', $payload['description']);
        $this->assertSame('بجنورد', $payload['city']['name']);
        $this->assertSame('مطب مرکزی', $payload['address'][0]['name']);
        $this->assertSame('37.4742', $payload['address'][0]['Latitude']);
        $this->assertSame('instagram', $payload['socials'][0]['platform']['name']);
        $this->assertSame('عضو انجمن تخصصی', $payload['honor_badges'][0]['title']);
        $this->assertStringContainsString('/storage/doctors/avatar.jpg', $payload['photo_url']);

        foreach ([
            'user_id',
            'email',
            'phone',
            'Medical_system_number',
            'registration_status',
            'site_expires_at',
            'password',
        ] as $privateField) {
            $this->assertArrayNotHasKey($privateField, $payload);
        }

        $this->assertArrayNotHasKey('organization_id', $payload['address'][0]);
        $this->assertArrayNotHasKey('doctor_id', $payload['socials'][0]);
    }
}
