<?php

declare(strict_types=1);

namespace Tests\\Unit\\Portal\\Profile;

use App\\DTOs\\Portal\\Profile\\UpdateProfileDto;
use App\\Models\\Doctor;
use Tests\\TestCase;

final class UpdateProfileDtoTest extends TestCase
{
    public function test_public_search_metadata_is_included_in_profile_updates(): void
    {
        $dto = new UpdateProfileDto(
            doctor: new Doctor(),
            name: 'پزشک',
            family: 'نمونه',
            email: 'doctor@example.test',
            phone: null,
            caption: null,
            specialty_title: 'قلب و عروق',
            years_of_experience: 12,
            Medical_system_number: '۱۲۳۴۵',
            description: 'معرفی پزشک',
            meta_title: 'دکتر نمونه | متخصص قلب و عروق',
            meta_description: 'معرفی خدمات و دریافت نوبت از پزشک نمونه',
        );

        $this->assertSame(
            'دکتر نمونه | متخصص قلب و عروق',
            $dto->toArray()['meta_title']
        );
        $this->assertSame(
            'معرفی خدمات و دریافت نوبت از پزشک نمونه',
            $dto->toArray()['meta_description']
        );
    }
}
