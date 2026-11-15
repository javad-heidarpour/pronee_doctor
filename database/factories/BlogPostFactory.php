<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BlogPostFactory extends Factory
{
    public function definition(): array
    {
        $titles = [
            'راهنمای جامع جراحی اسلیو معده',
            'آبدومینوپلاستی یا ورزش شکم؟',
            'تزریق چربی صورت و بدن',
            'مراقبت‌های بعد از جراحی لیپوساکشن',
            'تفاوت پروتز و تزریق چربی در ماموپلاستی',
            'چه کسانی کاندیدای جراحی چاقی هستند؟',
            'عوارض احتمالی رینوپلاستی و راه‌های کاهش آن',
            'بای پس معده یا اسلیو؟ کدام بهتر است؟',
        ];

        $excerpts = [
            'همه چیز درباره جراحی کاهش وزن، شرایط و مراقبت‌های قبل و بعد از عمل...',
            'چه زمانی جراحی شکم ضروری است؟ تفاوت‌ها را بدانید و بهترین تصمیم را بگیرید...',
            'مزایا و عوارض تزریق چربی خودی، ماندگاری و نکات کلیدی که باید بدانید...',
            'راهنمای کامل مراقبت بعد از عمل برای رسیدن به بهترین نتیجه ممکن...',
            'مقایسه دو روش پرکاربرد در جراحی پستان و کمک به انتخاب بهترین روش...',
        ];

        $title = $this->faker->randomElement($titles);

        return [
            'doctor_id' => \App\Models\Doctor::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => $this->faker->randomElement($excerpts),
            'content' => $this->faker->paragraphs(5, true),
            'image' => null,
            'published_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'is_published' => true,
            'views_count' => $this->faker->numberBetween(100, 5000),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
            'published_at' => null,
        ]);
    }
}