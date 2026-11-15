<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class SpecialtyFactory extends Factory
{
    public function definition(): array
    {
        $specialties = [
            ['name' => 'آبدومینوپلاستی', 'icon' => '🧬', 'description' => 'رفع افتادگی و شلی عضلات شکم و پیکرتراشی شکمی'],
            ['name' => 'ماموپلاستی', 'icon' => '🌸', 'description' => 'افزایش، کاهش یا بازگردانی تقارن پستان'],
            ['name' => 'لیپوساکشن', 'icon' => '💉', 'description' => 'حذف چربی‌های موضعی و کانتورینگ بدن'],
            ['name' => 'پیکرتراشی', 'icon' => '🏋️', 'description' => 'ترکیب لیپوساکشن و جراحی پوست اضافه'],
            ['name' => 'پروتز پستان', 'icon' => '✨', 'description' => 'افزایش حجم پستان با پروتزهای باکیفیت'],
            ['name' => 'تزریق چربی', 'icon' => '💧', 'description' => 'بازگرداندن حجم با چربی خود فرد'],
            ['name' => 'جراحی چاقی', 'icon' => '⚖️', 'description' => 'روش‌های جراحی کاهش وزن'],
            ['name' => 'اسلیو معده', 'icon' => '🍽️', 'description' => 'کاهش حجم معده به شکل آستینی'],
            ['name' => 'بای پس معده', 'icon' => '🔄', 'description' => 'تغییر مسیر دستگاه گوارش'],
            ['name' => 'رینوپلاستی', 'icon' => '👃', 'description' => 'جراحی زیبایی و ترمیمی بینی'],
        ];

        $specialty = $this->faker->randomElement($specialties);

        return [
            'doctor_id' => \App\Models\Doctor::factory(),
            'name' => $specialty['name'],
            'slug' => Str::slug($specialty['name']),
            'icon' => $specialty['icon'],
            'description' => $specialty['description'],
            'sort_order' => $this->faker->numberBetween(0, 20),
            'is_active' => true,
        ];
    }
}