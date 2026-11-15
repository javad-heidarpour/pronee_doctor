<?php
// database/seeders/ReviewSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\Review;
use Carbon\Carbon;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::all();

        $sampleReviews = [
            [
                'patient_name' => 'علی محمدی',
                'rating' => 5,
                'comment' => 'پزشک بسیار با تجربه و مهربان بودند. تشخیص دقیق و درمان مؤثر. کاملاً راضی هستم.',
                'is_approved' => true,
            ],
            [
                'patient_name' => 'فاطمه احمدی',
                'rating' => 4,
                'comment' => 'برخورد خوب و توضیحات کامل. فقط زمان انتظار کمی طولانی بود.',
                'is_approved' => true,
            ],
            [
                'patient_name' => 'محمد رضایی',
                'rating' => 5,
                'comment' => 'بهترین پزشکی که تا حالا مراجعه کردم. واقعاً به حرف‌های بیمار گوش می‌دهند.',
                'is_approved' => true,
            ],
            [
                'patient_name' => 'زهرا کریمی',
                'rating' => 3,
                'comment' => 'تشخیص خوب بود ولی برخورد منشی مطب مناسب نبود.',
                'is_approved' => true,
            ],
            [
                'patient_name' => 'حسین نوری',
                'rating' => 5,
                'comment' => 'عمل جراحی بسیار موفقیت‌آمیز بود. ممنون از تیم پزشکی.',
                'is_approved' => true,
            ],
            [
                'patient_name' => 'مریم صادقی',
                'rating' => 4,
                'comment' => 'پزشک حاذق و دلسوز. توضیحات کامل درباره بیماری و درمان دادند.',
                'is_approved' => true,
            ],
            [
                'is_anonymous' => true,
                'rating' => 5,
                'comment' => 'واقعاً عالی بودند. پیشنهاد می‌کنم.',
                'is_approved' => true,
            ],
            [
                'patient_name' => 'رضا جعفری',
                'rating' => 2,
                'comment' => 'زمان انتظار خیلی زیاد بود. بیش از ۲ ساعت منتظر ماندم.',
                'is_approved' => true,
            ],
        ];

        foreach ($doctors as $doctor) {
            // هر پزشک ۵ تا ۸ نظر تصادفی دریافت می‌کند
            $reviewCount = rand(5, 8);
            
            for ($i = 0; $i < $reviewCount; $i++) {
                $review = $sampleReviews[array_rand($sampleReviews)];
                
                Review::create([
                    'doctor_id' => $doctor->id,
                    'patient_name' => $review['patient_name'] ?? null,
                    'rating' => $review['rating'],
                    'comment' => $review['comment'],
                    'is_approved' => $review['is_approved'],
                    'is_anonymous' => $review['is_anonymous'] ?? false,
                    'created_at' => Carbon::now()->subDays(rand(1, 90)),
                ]);
            }
        }

        $this->command->info('✅ نظرات نمونه با موفقیت ایجاد شدند.');
    }
}