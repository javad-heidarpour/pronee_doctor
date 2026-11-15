<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\City;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['name' => 'تهران', 'slug' => 'tehran', 'province' => 'تهران', 'code' => '021'],
            ['name' => 'بجنورد', 'slug' => 'bojnourd', 'province' => 'بجنورد', 'code' => '058'],
            ['name' => 'اصفهان', 'slug' => 'isfahan', 'province' => 'اصفهان', 'code' => '031'],
            ['name' => 'شیراز', 'slug' => 'shiraz', 'province' => 'فارس', 'code' => '071'],
            ['name' => 'تبریز', 'slug' => 'tabriz', 'province' => 'آذربایجان شرقی', 'code' => '041'],
            ['name' => 'مشهد', 'slug' => 'mashhad', 'province' => 'خراسان رضوی', 'code' => '051'],
            ['name' => 'اهواز', 'slug' => 'ahvaz', 'province' => 'خوزستان', 'code' => '061'],
            ['name' => 'کرمان', 'slug' => 'kerman', 'province' => 'کرمان', 'code' => '034'],
            ['name' => 'قم', 'slug' => 'qom', 'province' => 'قم', 'code' => '025'],
            ['name' => 'کرج', 'slug' => 'karaj', 'province' => 'البرز', 'code' => '026'],
            ['name' => 'رشت', 'slug' => 'rasht', 'province' => 'گیلان', 'code' => '013'],
        ];

        foreach ($cities as $city) {
            City::create($city);
        }
    }
}