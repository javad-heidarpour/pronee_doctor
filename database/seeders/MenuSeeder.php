<?php

namespace Database\Seeders;

use App\Models\Menu;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menus = [
            // منوهای پنل پزشک
            [
                'title' => 'داشبورد',
                'route_name' => 'portal.dashboard',
                'icon' => 'IconDashboard',
                'order' => 1,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ],
            [
                'title' => 'پروفایل',
                'route_name' => 'portal.profile.edit',
                'icon' => 'IconUser',
                'order' => 2,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ],
            [
                'title' => 'مطب‌ها',
                'route_name' => 'portal.clinics.index',
                'icon' => 'IconBuilding',
                'order' => 3,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ],
            [
                'title' => 'ساعات کاری',
                'route_name' => 'portal.working-hours.index',
                'icon' => 'IconClock',
                'order' => 4,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ],
            [
                'title' => 'گالری تصاویر',
                'route_name' => 'portal.gallery.index',
                'icon' => 'IconPhoto',
                'order' => 5,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ],
            [
                'title' => 'نظرات بیماران',
                'route_name' => 'portal.reviews.index',
                'icon' => 'IconChat',
                'order' => 6,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ],
            [
                'title' => 'لیست بیماران',
                'route_name' => 'portal.patients.index',
                'icon' => 'IconUsers',
                'order' => 7,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ]

            ,
            [
                'title' => 'مدیریت شیفت‌ها',
                'route_name' => 'portal.shifts.index',
                'icon' => 'IconCalendar',
                'order' => 8,
                'guard' => 'doctor',
                'is_active' => true,
                'show_in_sidebar' => true,
            ],
        ];

        foreach ($menus as $menu) {
            Menu::create($menu);
        }

        $this->command->info('✅ منوهای پیش‌فرض با موفقیت ایجاد شدند.');
    }
}
