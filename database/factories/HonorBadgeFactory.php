<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class HonorBadgeFactory extends Factory
{
    public function definition(): array
    {
        $badges = [
            ['title' => 'برگزیده همایش بین‌المللی جراحی چاقی', 'icon' => '🏆'],
            ['title' => 'دارنده گواهی فلوشیپ اروپا', 'icon' => '🎓'],
            ['title' => 'عضو افتخاری انجمن جراحان پلاستیک ایران', 'icon' => '🎖️'],
            ['title' => '۲۰۰۰+ عمل موفق', 'icon' => '✨'],
            ['title' => 'مقالات علمی در ژورنال‌های معتبر', 'icon' => '📚'],
            ['title' => 'جراح برتر سال ۱۴۰۲', 'icon' => '🥇'],
            ['title' => 'رضایت ۹۸٪ بیماران', 'icon' => '⭐'],
            ['title' => 'عضو انجمن جراحان آمریکا', 'icon' => '🌍'],
        ];

        $badge = $this->faker->randomElement($badges);

        return [
            'doctor_id' => \App\Models\Doctor::factory(),
            'title' => $badge['title'],
            'icon' => $badge['icon'],
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }
}