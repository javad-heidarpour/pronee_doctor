<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id' => \App\Models\Doctor::factory(),
            'phone' => '۰۲۱-' . $this->faker->numerify('########'),
            'mobile' => '۰۹۱۲' . $this->faker->numerify('#######'),
            'email' => $this->faker->safeEmail(),
            'address' => 'تهران، ' . $this->faker->streetAddress() . '، کلینیک تخصصی جراحی',
            'city' => 'تهران',
            'country' => 'IR',
            'working_hours' => [
                'sat-wed' => '۹-۲۰',
                'thu' => '۹-۱۴',
                'fri' => 'تعطیل',
            ],
            'social_links' => [
                'instagram' => 'https://instagram.com/' . $this->faker->userName(),
                'telegram' => 'https://t.me/' . $this->faker->userName(),
                'whatsapp' => 'https://wa.me/98' . $this->faker->numerify('##########'),
            ],
        ];
    }
}