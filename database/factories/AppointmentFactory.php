<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $services = [
            'آبدومینوپلاستی', 'ماموپلاستی', 'لیپوساکشن', 'پیکرتراشی',
            'پروتز پستان', 'تزریق چربی', 'جراحی چاقی', 'اسلیو معده', 'بای پس معده',
        ];

        $statuses = ['pending', 'contacted', 'confirmed', 'cancelled'];

        return [
            'doctor_id' => \App\Models\Doctor::factory(),
            'name' => $this->faker->name(),
            'phone' => '09' . $this->faker->numerify('#########'),
            'service' => $this->faker->randomElement($services),
            'preferred_date' => $this->faker->dateTimeBetween('now', '+2 months'),
            'message' => $this->faker->optional()->sentence(),
            'status' => $this->faker->randomElement($statuses),
            'contacted_at' => $this->faker->optional()->dateTime(),
            'admin_note' => $this->faker->optional()->sentence(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }
}