<?php
// database/seeders/DoctorImageSeeder.php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Doctor;
use App\Models\DoctorImage;

class DoctorImageSeeder extends Seeder
{
    public function run(): void
    {
        $doctors = Doctor::all();

        // نمونه تصاویر از Unsplash (می‌توانید با تصاویر واقعی جایگزین کنید)
        $sampleImages = [
            [
                'category' => 'clinic',
                'caption' => 'فضای داخلی مطب',
                'image_url' => 'https://images.unsplash.com/photo-1629909613654-28e377c37b09?w=800',
            ],
            [
                'category' => 'clinic',
                'caption' => 'اتاق انتظار',
                'image_url' => 'https://images.unsplash.com/photo-1631217868264-e5b942f0151f?w=800',
            ],
            [
                'category' => 'equipment',
                'caption' => 'تجهیزات پیشرفته پزشکی',
                'image_url' => 'https://images.unsplash.com/photo-1516549655169-df83a0774c82?w=800',
            ],
            [
                'category' => 'equipment',
                'caption' => 'دستگاه سونوگرافی',
                'image_url' => 'https://images.unsplash.com/photo-1559757175-5700dde675bc?w=800',
            ],
            [
                'category' => 'team',
                'caption' => 'تیم پزشکی ما',
                'image_url' => 'https://images.unsplash.com/photo-1551190822-a9333d879b1f?w=800',
            ],
            [
                'category' => 'certificate',
                'caption' => 'گواهینامه تخصصی',
                'image_url' => 'https://images.unsplash.com/photo-1586936893354-362ad6ae47ba?w=800',
            ],
            [
                'category' => 'clinic',
                'caption' => 'نمای بیرونی مطب',
                'image_url' => 'https://images.unsplash.com/photo-1538108149393-fbbd81895907?w=800',
            ],
            [
                'category' => 'equipment',
                'caption' => 'اتاق معاینه',
                'image_url' => 'https://images.unsplash.com/photo-1581056771107-24ca5f033842?w=800',
            ],
        ];

        foreach ($doctors as $doctor) {
            // هر پزشک ۶ تا ۸ تصویر تصادفی دریافت می‌کند
            $imageCount = rand(6, 8);
            $selectedImages = array_rand($sampleImages, $imageCount);
            
            if (!is_array($selectedImages)) {
                $selectedImages = [$selectedImages];
            }

            $order = 1;
            foreach ($selectedImages as $index) {
                $image = $sampleImages[$index];
                
                DoctorImage::create([
                    'doctor_id' => $doctor->id,
                    'image_url' => $image['image_url'],
                    'caption' => $image['caption'],
                    'category' => $image['category'],
                    'sort_order' => $order++,
                    'is_active' => true,
                ]);
            }
        }

        $this->command->info('✅ تصاویر گالری با موفقیت ایجاد شدند.');
    }
}