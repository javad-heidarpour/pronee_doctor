<?php

declare(strict_types=1);

namespace App\Actions\Portal\Appointment;

use App\Models\Appointment;
use App\Models\Doctor;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportPortalAppointmentsAction
{
    /**
     * Export all matching appointments. The controller supplies only accessible clinic IDs.
     */
    public function handle(Doctor $doctor, array $clinicIds, array $filters): StreamedResponse
    {
        $query = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->with(['clinic', 'visitReason', 'latestPayment']);

        if (!empty($filters['clinic_id'])) {
            $query->where('clinic_id', (int) $filters['clinic_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $paymentStatus = $filters['payment_status'];
            $query->whereHas('latestPayment', fn ($paymentQuery) => $paymentQuery
                ->where('status', $paymentStatus));
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('appointment_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('appointment_date', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($patientQuery) use ($search): void {
                $patientQuery
                    ->where('patient_name', 'like', "%{$search}%")
                    ->orWhere('patient_phone', 'like', "%{$search}%")
                    ->orWhere('patient_national_code', 'like', "%{$search}%");
            });
        }

        return response()->streamDownload(function () use ($query): void {
            $output = fopen('php://output', 'wb');

            if ($output === false) {
                return;
            }

            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                'شناسه نوبت',
                'نام بیمار',
                'شماره تماس',
                'تاریخ',
                'ساعت',
                'مطب',
                'دلیل مراجعه',
                'وضعیت نوبت',
                'وضعیت پرداخت',
                'مبلغ (ریال)',
                'شماره پیگیری نوبت',
            ], ',', '"', '');

            $query->orderBy('appointment_date')
                ->orderBy('appointment_time')
                ->orderBy('id')
                ->chunkById(500, function ($appointments) use ($output): void {
                    foreach ($appointments as $appointment) {
                        $status = match ($appointment->status) {
                            'pending' => 'در انتظار',
                            'confirmed' => 'تأیید شده',
                            'visited' => 'ویزیت شده',
                            'cancelled' => 'لغو شده',
                            default => 'نامشخص',
                        };

                        $paymentStatus = match ($appointment->latestPayment?->status) {
                            'paid' => 'پرداخت شده',
                            'pending', 'initiated' => 'در انتظار پرداخت',
                            'failed' => 'پرداخت ناموفق',
                            'cancelled' => 'لغو شده',
                            'refunded' => 'مسترد شده',
                            default => 'بدون پرداخت',
                        };

                        $row = [
                            $appointment->id,
                            $appointment->patient_name,
                            $appointment->patient_phone,
                            $appointment->appointment_date
                                ? (string) $appointment->appointment_date
                                : '',
                            $appointment->appointment_time
                                ? substr((string) $appointment->appointment_time, 0, 5)
                                : '',
                            $appointment->clinic?->name,
                            $appointment->visitReason?->title,
                            $status,
                            $paymentStatus,
                            $appointment->latestPayment?->amount_rial ?? $appointment->fee_rial,
                            $appointment->visit_number,
                        ];

                        fputcsv($output, array_map(
                            static fn ($value) => self::safeCell($value),
                            $row
                        ), ',', '"', '');
                    }
                });

            fclose($output);
        }, 'appointments-' . now()->format('Ymd-His') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, private',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private static function safeCell(mixed $value): string
    {
        $value = (string) ($value ?? '');

        // Escape spreadsheet formulas in patient-entered values.
        if ($value !== '' && preg_match('/^[=+@\-\t\r]/u', $value)) {
            return "'" . $value;
        }

        return $value;
    }
}
