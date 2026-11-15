<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Contact;
use App\Models\Doctor;
use App\Models\HonorBadge;
use App\Models\Specialty;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ ساخت یا دریافت پزشک اصلی (جلوگیری از خطای Duplicate)
        $mainDoctor = Doctor::firstOrCreate(
            ['slug' => 'dr-sara-ahmadi'],
            [
                'name' => 'دکتر سارا احمدی',
                'description' => 'جراح عمومی و فلوشیپ جراحی زیبایی و متابولیک با بیش از ۱۵ سال تجربه موفق در زمینه جراحی‌های زیبایی و درمان چاقی، با بهره‌گیری از پیشرفته‌ترین تکنیک‌های روز دنیا و رعایت بالاترین استانداردهای ایمنی.',
                'specialty_title' => 'جراح عمومی و فلوشیپ جراحی زیبایی و چاقی',
                'years_of_experience' => 15,
                'satisfaction_rate' => 98.00,
                'is_active' => true,
            ]
        );

        // 2️⃣ اطلاعات تماس
        Contact::updateOrCreate(
            ['doctor_id' => $mainDoctor->id],
            [
                'phone' => '۰۲۱-۱۲۳۴۵۶۷۸',
                'mobile' => '۰۹۱۲۱۲۳۴۵۶۷',
                'address' => 'تهران، خیابان ولیعصر، بالاتر از میدان ونک، کلینیک تخصصی جراحی',
                'working_hours' => ['sat-wed' => '۹-۲۰', 'thu' => '۹-۱۴', 'fri' => 'تعطیل'],
                'social_links' => [
                    'instagram' => 'https://instagram.com/dr.ahmadi',
                    'telegram' => 'https://t.me/dr_ahmadi',
                ],
            ]
        );

        // 3️⃣ تخصص‌ها (اگر وجود نداشتند ایجاد می‌شوند)
        $mainSpecialties = [
            ['name' => 'آبدومینوپلاستی', 'icon' => '🧬', 'description' => 'رفع افتادگی و شلی عضلات شکم'],
            ['name' => 'ماموپلاستی', 'icon' => '🌸', 'description' => 'افزایش، کاهش یا بازگردانی تقارن پستان'],
            ['name' => 'لیپوساکشن', 'icon' => '💉', 'description' => 'حذف چربی‌های موضعی و کانتورینگ بدن'],
            ['name' => 'پیکرتراشی', 'icon' => '🏋️', 'description' => 'ترکیب لیپوساکشن و جراحی پوست اضافه'],
            ['name' => 'پروتز پستان', 'icon' => '✨', 'description' => 'افزایش حجم پستان با پروتزهای باکیفیت'],
            ['name' => 'تزریق چربی', 'icon' => '💧', 'description' => 'بازگرداندن حجم با چربی خود فرد'],
            ['name' => 'جراحی چاقی', 'icon' => '⚖️', 'description' => 'روش‌های جراحی کاهش وزن'],
            ['name' => 'اسلیو معده', 'icon' => '🍽️', 'description' => 'کاهش حجم معده به شکل آستینی'],
            ['name' => 'بای پس معده', 'icon' => '🔄', 'description' => 'تغییر مسیر دستگاه گوارش'],
        ];

        foreach ($mainSpecialties as $index => $spec) {
            Specialty::firstOrCreate(
                ['doctor_id' => $mainDoctor->id, 'name' => $spec['name']],
                array_merge($spec, ['sort_order' => $index])
            );
        }

        // 4️⃣ نظرات بیماران
        $mainTestimonials = [
            ['name' => 'مریم رضایی', 'comment' => 'بعد از جراحی اسلیو معده، کیفیت زندگی من کاملاً متحول شد.', 'service' => 'اسلیو معده'],
            ['name' => 'احمد کریمی', 'comment' => 'لیپوساکشن با کمترین درد و عالی نتیجه داد.', 'service' => 'لیپوساکشن'],
            ['name' => 'سارا محمدی', 'comment' => 'ماموپلاستی افزایشی با پروتز، نتیجه فراتر از انتظارم بود.', 'service' => 'پروتز پستان'],
        ];

        foreach ($mainTestimonials as $index => $test) {
            Testimonial::firstOrCreate(
                ['doctor_id' => $mainDoctor->id, 'name' => $test['name']],
                array_merge($test, ['is_approved' => true, 'sort_order' => $index])
            );
        }

        // 5️⃣ مقالات
        $mainPosts = [
            ['title' => 'راهنمای جامع جراحی اسلیو معده', 'slug' => 'gastric-sleeve-guide', 'excerpt' => 'همه چیز درباره جراحی کاهش وزن...', 'published_at' => now()->subDays(65)],
            ['title' => 'آبدومینوپلاستی یا ورزش شکم؟', 'slug' => 'abdominoplasty-vs-exercise', 'excerpt' => 'چه زمانی جراحی شکم ضروری است؟...', 'published_at' => now()->subDays(47)],
            ['title' => 'تزریق چربی صورت و بدن', 'slug' => 'fat-grafting-face-body', 'excerpt' => 'مزایا و عوارض تزریق چربی خودی...', 'published_at' => now()->subDays(8)],
        ];

        

        // 6️⃣ افتخارات
        $mainBadges = [
            'برگزیده همایش بین‌المللی جراحی چاقی',
            'دارنده گواهی فلوشیپ اروپا',
            'عضو افتخاری انجمن جراحان پلاستیک ایران',
            '۲۰۰۰+ عمل موفق',
        ];

        foreach ($mainBadges as $index => $badge) {
            HonorBadge::firstOrCreate(
                ['doctor_id' => $mainDoctor->id, 'title' => $badge],
                ['sort_order' => $index]
            );
        }

        // 🟢 داده‌های تصادفی (فقط اگر جدول خالی باشد یا تعداد کم باشد)
        if (Doctor::count() < 5) {
            Doctor::factory()
                ->count(3)
                ->has(Contact::factory())
                ->has(Specialty::factory()->count(5))
                ->has(Testimonial::factory()->count(6))
             
                ->has(HonorBadge::factory()->count(4))
                ->create();
        }

        if (Appointment::count() < 50) {
            Appointment::factory()
                ->count(30)
                ->for($mainDoctor)
                ->create();
        }

        $this->command->info('✅ تمام داده‌ها با موفقیت seed شدند (بدون خطای تکراری).');
    }
}

