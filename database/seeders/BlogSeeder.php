<?php
// database/seeders/BlogSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        // ایجاد دسته‌بندی‌ها
        $categories = [
            [
                'name' => 'سلامت عمومی',
                'slug' => 'general-health',
                'description' => 'مقالات مربوط به سلامت عمومی و پیشگیری از بیماری‌ها',
                'icon' => '🏥',
                'color' => 'teal',
                'sort_order' => 1,
            ],
            [
                'name' => 'تغذیه سالم',
                'slug' => 'nutrition',
                'description' => 'راهنمای تغذیه سالم و رژیم‌های غذایی',
                'icon' => '🥗',
                'color' => 'green',
                'sort_order' => 2,
            ],
            [
                'name' => 'ورزش و تناسب اندام',
                'slug' => 'fitness',
                'description' => 'تمرینات ورزشی و حفظ تناسب اندام',
                'icon' => '🏃',
                'color' => 'blue',
                'sort_order' => 3,
            ],
            [
                'name' => 'سلامت روان',
                'slug' => 'mental-health',
                'description' => 'مقالات مربوط به سلامت روان و کاهش استرس',
                'icon' => '🧠',
                'color' => 'purple',
                'sort_order' => 4,
            ],
            [
                'name' => 'پزشکی کودکان',
                'slug' => 'pediatrics',
                'description' => 'سلامت کودکان و نوزادان',
                'icon' => '👶',
                'color' => 'pink',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $categoryData) {
            Category::create($categoryData);
        }

        // ایجاد مقالات نمونه
        $posts = [
            [
                'category_id' => 1,
                'title' => '۱۰ عادت سالم برای زندگی بهتر',
                'slug' => '10-healthy-habits',
                'excerpt' => 'در این مقاله با ۱۰ عادت ساده اما مؤثر برای بهبود کیفیت زندگی آشنا می‌شوید.',
                'content' => $this->generateSampleContent('۱۰ عادت سالم'),
                'featured_image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800',
                'reading_time' => 5,
                'is_featured' => true,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(1),
            ],
            [
                'category_id' => 2,
                'title' => 'راهنمای کامل تغذیه سالم',
                'slug' => 'complete-nutrition-guide',
                'excerpt' => 'همه چیز درباره تغذیه سالم، از انتخاب مواد غذایی تا برنامه‌ریزی وعده‌ها.',
                'content' => $this->generateSampleContent('تغذیه سالم'),
                'featured_image' => 'https://images.unsplash.com/photo-1490645935967-10de6ba17061?w=800',
                'reading_time' => 8,
                'is_featured' => true,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(3),
            ],
            [
                'category_id' => 3,
                'title' => 'تمرینات ورزشی در خانه',
                'slug' => 'home-workout-exercises',
                'excerpt' => 'با این تمرینات ساده در خانه، تناسب اندام خود را حفظ کنید.',
                'content' => $this->generateSampleContent('ورزش در خانه'),
                'featured_image' => 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?w=800',
                'reading_time' => 6,
                'is_featured' => false,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(5),
            ],
            [
                'category_id' => 4,
                'title' => 'مدیریت استرس در زندگی روزمره',
                'slug' => 'stress-management',
                'excerpt' => 'تکنیک‌های مؤثر برای کاهش استرس و افزایش آرامش درونی.',
                'content' => $this->generateSampleContent('مدیریت استرس'),
                'featured_image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800',
                'reading_time' => 7,
                'is_featured' => true,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(7),
            ],
            [
                'category_id' => 5,
                'title' => 'تغذیه مناسب برای کودکان',
                'slug' => 'child-nutrition',
                'excerpt' => 'راهنمای جامع تغذیه کودکان در سنین مختلف.',
                'content' => $this->generateSampleContent('تغذیه کودکان'),
                'featured_image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800',
                'reading_time' => 9,
                'is_featured' => false,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(10),
            ],
            [
                'category_id' => 1,
                'title' => 'پیشگیری از بیماری‌های فصلی',
                'slug' => 'seasonal-disease-prevention',
                'excerpt' => 'با رعایت این نکات ساده، از بیماری‌های فصلی در امان بمانید.',
                'content' => $this->generateSampleContent('پیشگیری از بیماری'),
                'featured_image' => 'https://images.unsplash.com/photo-1506126613408-eca07ce68773?w=800',
                'reading_time' => 4,
                'is_featured' => false,
                'is_published' => true,
                'published_at' => Carbon::now()->subDays(12),
            ],
        ];

        foreach ($posts as $postData) {
            Post::create($postData);
        }
    }

    private function generateSampleContent(string $topic): string
    {
        return "
            <h2>مقدمه</h2>
            <p>در این مقاله به بررسی جامع {$topic} می‌پردازیم. این موضوع یکی از مهم‌ترین مباحث در حوزه سلامت است که توجه ویژه‌ای را می‌طلبد.</p>
            
            <h2>اهمیت موضوع</h2>
            <p>{$topic} تأثیر مستقیمی بر کیفیت زندگی ما دارد. تحقیقات نشان داده است که رعایت اصول مرتبط با این موضوع می‌تواند به طور قابل توجهی سلامت جسمی و روانی ما را بهبود بخشد.</p>
            
            <h2>نکات کلیدی</h2>
            <ul>
                <li>نکته اول: اهمیت برنامه‌ریزی منظم</li>
                <li>نکته دوم: توجه به نیازهای فردی</li>
                <li>نکته سوم: مشورت با متخصصان</li>
                <li>نکته چهارم: پایداری در اجرا</li>
            </ul>
            
            <h2>راهکارهای عملی</h2>
            <p>برای بهره‌مندی از مزایای {$topic}، می‌توانید از راهکارهای زیر استفاده کنید:</p>
            <ol>
                <li>شروع با تغییرات کوچک و تدریجی</li>
                <li>ثبت پیشرفت‌های خود</li>
                <li>جستجوی حمایت از اطرافیان</li>
                <li>صبور بودن و ادامه دادن</li>
            </ol>
            
            <h2>نتیجه‌گیری</h2>
            <p>در نهایت، {$topic} یک سفر است نه یک مقصد. با تعهد و پشتکار، می‌توانید به اهداف خود دست یابید و زندگی سالم‌تری داشته باشید.</p>
        ";
    }
}