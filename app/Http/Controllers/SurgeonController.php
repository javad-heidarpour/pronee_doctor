<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class SurgeonController extends Controller
{
   public function index()
    {
        $surgeon = [
            'name' => 'دکتر فرهاد وفایی',
            'initial' => 'ف‌و',
        'photo' => asset('storage/surgeons/Dr.Farhad.Vafaei.jpg'),

            'description' => 'بیش از ۲۰ سال تجربه موفق در زمینه جراحی‌های زیبایی و درمان چاقی...'
        ];

        $Specialty = [
            ['id' => 1, 'name' => 'آبدومینوپلاستی (بازسازی شکم)', 'icon' => '🧬', 'description' => 'رفع افتادگی و شلی عضلات دیواره شکم...'],
            ['id' => 2, 'name' => 'ماموپلاستی (جراحی پستان)', 'icon' => '🌸', 'description' => 'افزایش، کاهش یا بازگردانی تقارن پستان‌ها با استفاده از روش‌های مدرن'],
            ['id' => 3, 'name' => 'لیپوساکشن (چربی‌کشی)', 'icon' => '💉', 'description' => 'حذف چربی‌های موضعی مقاوم به ورزش و رژیم، کانتورینگ بدن.'],
            ['id' => 4, 'name' =>'پیکرتراشی (Body Contouring)' , 'icon' =>'🏋️' , 'description' =>'ترکیب لیپوساکشن و جراحی‌های برداشت پوست اضافه برای فرم‌دهی نهایی بدن.' ],
            ['id' => 5, 'name' => 'پروتز پستان', 'icon' =>'✨' , 'description' =>'افزایش حجم و بهبود فرم پستان با استفاده از پروتزهای باکیفیت و ایمن.' ],
            ['id' => 6, 'name' => 'تزریق چربی (Lipofilling)', 'icon' => '💧', 'description' =>'بازگرداندن حجم به نواحی مختلف صورت و بدن با استفاده از چربی خود فرد.' ],
            ['id' => 7, 'name' =>'جراحی چاقی (Bariatric Surgery)' , 'icon' =>'⚖️' , 'description' =>'روش‌های جراحی کاهش وزن برای افراد با چاقی شدید و مشکلات متابولیک.' ],
            ['id' =>8, 'name' => 'اسلیو معده (Gastric Sleeve)', 'icon' =>'🍽️' , 'description' =>'کاهش حجم معده به شکل آستینی، موثر در کاهش اشتها و وزن.' ],
            ['id' => 9, 'name' => 'بای پس معده (Gastric Bypass)', 'icon' =>'🔄' , 'description' => 'تغییر مسیر دستگاه گوارش برای جذب کمتر کالری و کنترل دیابت.'],


            
            // ... بقیه تخصص‌ها
        ];

        return Inertia::render('SurgeonPage', [
            'surgeon' => $surgeon,
            'Specialty' => $Specialty,
        ]);
    }
}
