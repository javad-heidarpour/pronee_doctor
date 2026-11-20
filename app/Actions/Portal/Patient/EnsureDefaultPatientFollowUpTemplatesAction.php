<?php

declare(strict_types=1);

namespace App\Actions\Portal\Patient;

use App\Models\Doctor;
use App\Models\PatientFollowUpTemplate;
use Illuminate\Support\Facades\DB;

final class EnsureDefaultPatientFollowUpTemplatesAction
{
    private const DEFAULTS = [
        ['پیگیری پس از ویزیت', 'general', 'تماس برای بررسی وضعیت عمومی بیمار', 3, 'call'],
        ['یادآوری انجام آزمایش', 'lab', 'پیگیری انجام آزمایش و دریافت نتیجه', 7, 'call'],
        ['کنترل بیماری مزمن', 'chronic', 'پیگیری وضعیت و پایبندی به برنامه درمانی', 30, 'call'],
        ['کنترل اولیه پس از جراحی', 'post_op', 'تماس برای بررسی وضعیت پس از عمل و ارجاع موارد هشداردهنده به پزشک', 1, 'call'],
        ['پیگیری دوره‌ای پس از جراحی', 'surgery', 'بررسی روند بهبودی و نیاز به مراجعه مجدد', 7, 'review'],
        ['پیگیری خدمات زیبایی', 'cosmetic', 'بررسی رضایت و عوارض گزارش‌شده پس از خدمت', 3, 'call'],
    ];

    public function handle(Doctor $doctor): void
    {
        DB::transaction(function () use ($doctor): void {
            // Serialize first-time seeding for this doctor without changing existing templates.
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

            foreach (self::DEFAULTS as [$title, $category, $description, $days, $method]) {
                PatientFollowUpTemplate::query()->firstOrCreate(
                    ['doctor_id' => $doctor->id, 'title' => $title],
                    [
                        'category' => $category,
                        'description' => $description,
                        'default_days' => $days,
                        'contact_method' => $method,
                        'send_sms' => false,
                        'is_active' => true,
                    ]
                );
            }
        });
    }
}
