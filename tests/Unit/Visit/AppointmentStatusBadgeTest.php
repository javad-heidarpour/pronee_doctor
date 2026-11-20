<?php

declare(strict_types=1);

namespace Tests\Unit\Visit;

use App\Models\Appointment;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class AppointmentStatusBadgeTest extends TestCase
{
    public static function statusLabels(): array
    {
        return [
            'pending' => ['pending', 'در انتظار'],
            'contacted' => ['contacted', 'تماس گرفته شده'],
            'confirmed' => ['confirmed', 'تأیید شده'],
            'visited' => ['visited', 'ویزیت‌شده'],
            'legacy completed' => ['completed', 'ویزیت‌شده'],
            'cancelled' => ['cancelled', 'لغو شده'],
            'legacy canceled' => ['canceled', 'لغو شده'],
        ];
    }

    #[DataProvider('statusLabels')]
    public function test_it_exposes_a_patient_friendly_label_for_each_appointment_status(
        string $status,
        string $expectedLabel
    ): void {
        $appointment = new Appointment(['status' => $status]);

        $this->assertSame($expectedLabel, $appointment->status_badge);
    }
}
