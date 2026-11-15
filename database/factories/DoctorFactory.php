<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class DoctorFactory extends Factory
{
    public function definition(): array
    {
        $names = [
            'دکتر سارا احمدی',
            'دکتر محمد رضایی',
            'دکتر فاطمه محمدی',
            'دکتر علی کریمی',
            'دکتر مریم حسینی',
        ];

        $descriptions = [
            'جراح عمومی و فلوشیپ جراحی زیبایی و متابولیک با بیش از ۱۵ سال تجربه موفق در زمینه جراحی‌های زیبایی و درمان چاقی، با بهره‌گیری از پیشرفته‌ترین تکنیک‌های روز دنیا.',
            'متخصص جراحی پلاستیک و ترمیمی با تجربه بیش از ۲۰ سال در انجام جراحی‌های زیبایی صورت و بدن. عضو انجمن جراحان پلاستیک ایران.',
            'فلوشیپ جراحی چاقی و متابولیک از اروپا، با سابقه بیش از ۲۰۰۰ عمل موفق در زمینه اسلیو معده و بای‌پس.',
        ];

        $name = $this->faker->randomElement($names);

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'photo' => null,
            'description' => $this->faker->randomElement($descriptions),
            'meta_title' => $name . ' - جراح زیبایی و چاقی',
            'meta_description' => 'رزرو نوبت و مشاوره با ' . $name,
            'specialty_title' => 'جراح عمومی و فلوشیپ جراحی زیبایی',
            'years_of_experience' => $this->faker->numberBetween(10, 25),
            'satisfaction_rate' => $this->faker->randomFloat(2, 95, 99.99),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}