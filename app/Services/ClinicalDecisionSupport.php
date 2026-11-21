<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

final class ClinicalDecisionSupport
{
    private const MAX_VITAL_AGE_HOURS = 24;

    /**
     * Summarize whether minimum clinical context is available for clinician
     * review. "available" never means normal, safe, or clinically sufficient.
     *
     * @param array<string, mixed> $snapshot
     * @return array<string, mixed>
     */
    public function evaluate(array $snapshot, ?CarbonInterface $now = null): array
    {
        $now = $now ? Carbon::instance($now) : now();
        $allergies = is_array($snapshot['allergies'] ?? null) ? $snapshot['allergies'] : [];
        $medications = is_array($snapshot['medications'] ?? null) ? $snapshot['medications'] : [];
        $vitals = is_array($snapshot['vitals'] ?? null) ? $snapshot['vitals'] : [];

        $checks = [
            [
                'key' => 'allergies',
                'title' => 'بازبینی حساسیت‌ها',
                'status' => $allergies !== [] ? 'available' : 'unknown',
                'message' => $allergies !== []
                    ? count($allergies).' مورد حساسیت ثبت شده؛ نوع، واکنش و فعال‌بودن آن با بیمار تطبیق داده شود.'
                    : 'اطلاعات حساسیت در پرونده موجود نیست؛ وضعیت باید مستقیماً از بیمار پرسیده و ثبت شود.',
            ],
            [
                'key' => 'medications',
                'title' => 'تطبیق داروهای جاری',
                'status' => $medications !== [] ? 'available' : 'unknown',
                'message' => $medications !== []
                    ? count($medications).' دارو در پرونده ثبت شده؛ مصرف واقعی، دوز و تغییرات اخیر بررسی شود.'
                    : 'فهرست داروی جاری در پرونده موجود نیست؛ داروهای مصرفی بیمار اخذ و تطبیق داده شوند.',
            ],
        ];

        $latest = collect($vitals)
            ->filter(fn ($vital) => is_array($vital) && !empty($vital['measured_at']))
            ->sortByDesc(fn (array $vital) => Carbon::parse($vital['measured_at'])->timestamp)
            ->first();

        if ($latest === null) {
            $vitalStatus = 'unknown';
            $vitalMessage = 'اندازه‌گیری تاریخ‌دار در دسترس نیست؛ در صورت نیاز بالینی علائم حیاتی ثبت شود.';
        } else {
            $measuredAt = Carbon::parse($latest['measured_at']);

            if ($measuredAt->greaterThan($now->copy()->addMinutes(5))) {
                $vitalStatus = 'unknown';
                $vitalMessage = 'زمان آخرین اندازه‌گیری در آینده ثبت شده است؛ تاریخ و ساعت منبع باید اصلاح یا تأیید شود.';
            } elseif ($measuredAt->lessThan($now->copy()->subHours(self::MAX_VITAL_AGE_HOURS))) {
                $vitalStatus = 'stale';
                $vitalMessage = 'آخرین اندازه‌گیری بیش از ۲۴ ساعت قبل است؛ تازگی داده و نیاز به اندازه‌گیری مجدد بررسی شود.';
            } else {
                $vitalStatus = 'available';
                $vitalMessage = 'آخرین اندازه‌گیری در ۲۴ ساعت اخیر است؛ مقدار و تناسب آن با وضعیت فعلی بیمار همچنان باید بررسی شود.';
            }
        }

        $checks[] = [
            'key' => 'vitals',
            'title' => 'تازگی علائم حیاتی',
            'status' => $vitalStatus,
            'message' => $vitalMessage,
            'measured_at' => $latest['measured_at'] ?? null,
        ];

        $reviewCount = count(array_filter(
            $checks,
            fn (array $check): bool => $check['status'] !== 'available'
        ));

        return [
            'version' => 2,
            'checks' => $checks,
            'review_count' => $reviewCount,
            'summary' => $reviewCount > 0
                ? $reviewCount.' بخش نیازمند تکمیل یا بازبینی است.'
                : 'اطلاعات پایه در پرونده موجود است؛ این موضوع به معنی طبیعی‌بودن یا ایمن‌بودن وضعیت بیمار نیست.',
            'limitations' => [
                'no_diagnosis',
                'no_drug_interaction_check',
                'no_dose_recommendation',
                'no_automated_prescribing',
                'availability_does_not_mean_normal',
            ],
        ];
    }
}
