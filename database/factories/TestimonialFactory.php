<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TestimonialFactory extends Factory
{
    public function definition(): array
    {
        $services = [
            'اسلیو معده',
            'لیپوساکشن',
            'پروتز پستان',
            'آبدومینوپلاستی',
            'رینوپلاستی',
            'تزریق چربی',
            'بای پس معده',
            'ماموپلاستی',
        ];

        $comments = [
            'بعد از جراحی، کیفیت زندگی من کاملاً متحول شد. دکتر بسیار حرفه‌ای و با حوصله بودند.',
            'نتیجه عمل فراتر از انتظارم بود. تیم پزشکی فوق‌العاده‌ای دارند.',
            'مراقبت‌های بعد از عمل عالی بود و درد بسیار کمی داشتم. کاملاً راضی هستم.',
            'از مشاوره اولیه تا روز عمل، همه چیز حرفه‌ای و با دقت انجام شد.',
            'بهترین تصمیم زندگیم بود. ممنون از دکتر و تیم محترمشان.',
            'با اینکه استرس زیادی داشتم، اما دکتر با صبر و حوصله همه نگرانی‌هایم را برطرف کردند.',
            'طبیعی‌ترین نتیجه ممکن را گرفتم. واقعاً هنر دست دکتر مشخص است.',
        ];

        $names = [
            'مریم رضایی', 'احمد کریمی', 'سارا محمدی', 'علی حسینی',
            'زهرا نوری', 'رضا اکبری', 'فاطمه صادقی', 'محمد امینی',
            'نرگس موسوی', 'حسین جعفری',
        ];

        return [
            'doctor_id' => \App\Models\Doctor::factory(),
            'name' => $this->faker->randomElement($names),
            'comment' => $this->faker->randomElement($comments),
            'service' => $this->faker->randomElement($services),
            'rating' => $this->faker->numberBetween(4, 5),
            'is_approved' => true,
            'sort_order' => $this->faker->numberBetween(0, 20),
        ];
    }

    public function unapproved(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approved' => false,
        ]);
    }
}