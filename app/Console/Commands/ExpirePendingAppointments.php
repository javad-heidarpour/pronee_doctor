<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\AppointmentPayment;
use App\Models\TimeSlot;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ExpirePendingAppointments extends Command
{
    protected $signature = 'appointments:expire-pending';

    protected $description = 'Cancel expired unpaid appointments and release available time slots safely';

    public function handle(): int
    {
        $appointmentIds = Appointment::query()
            ->where('status', 'pending')
            ->whereNotNull('reservation_expires_at')
            ->where('reservation_expires_at', '<=', now())
            ->orderBy('id')
            ->limit(500)
            ->pluck('id');

        $cancelled = 0;
        $confirmed = 0;
        $waiting = 0;
        $stalePayments = 0;

        foreach ($appointmentIds as $appointmentId) {
            try {
                $result = DB::transaction(function () use ($appointmentId) {
                    $appointment = Appointment::query()
                        ->whereKey($appointmentId)
                        ->lockForUpdate()
                        ->first();

                    if (
                        !$appointment ||
                        $appointment->status !== 'pending' ||
                        !$appointment->reservation_expires_at ||
                        $appointment->reservation_expires_at->isFuture()
                    ) {
                        return 'skipped';
                    }

                    $payments = AppointmentPayment::query()
                        ->where('appointment_id', $appointment->id)
                        ->orderByDesc('id')
                        ->lockForUpdate()
                        ->get();

                    // پرداخت تأییدشده: نوبت را تأیید کن و اسلات را آزاد نکن.
                    if ($payments->contains('status', 'paid')) {
                        $appointment->status = 'confirmed';
                        $appointment->reservation_expires_at = null;

                        if (Schema::hasColumn('appointments', 'payment_status')) {
                            $appointment->payment_status = 'paid';
                        }

                        $appointment->save();

                        return 'confirmed';
                    }

                    // اگر هر تلاش پرداخت هنوز باز یا نامشخص است،
                    // بدون استعلام معتبر SEP نوبت را لغو نکن.
                    $hasUnresolvedPayment = $payments->contains(
                        fn($payment) => in_array(
                            $payment->status,
                            ['pending', 'initiated'],
                            true
                        )
                    );

                    if ($hasUnresolvedPayment) {
                        $stalePaymentIds = $payments
                            ->filter(function ($payment) {
                                return in_array($payment->status, ['pending', 'initiated'], true)
                                    && $payment->updated_at
                                    && $payment->updated_at->lte(now()->subMinutes(15));
                            })
                            ->pluck('id')
                            ->values()
                            ->all();

                        if ($stalePaymentIds !== []) {
                            Log::warning('Expired reservation has stale unresolved SEP payment', [
                                'appointment_id' => $appointment->id,
                                'payment_ids' => $stalePaymentIds,
                                'threshold_minutes' => 15,
                            ]);

                            return 'stale_waiting';
                        }

                        return 'waiting';
                    }

                    // در نبود پرداخت، یا وقتی تمام تلاش‌ها failed هستند،
                    // نوبت قابل لغو است.
                    $appointment->status = 'cancelled';
                    $appointment->reservation_expires_at = null;

                    if (Schema::hasColumn('appointments', 'payment_status')) {
                        $appointment->payment_status = 'failed';
                    }

                    $appointment->save();

                    $slot = TimeSlot::query()
                        ->whereKey($appointment->time_slot_id)
                        ->lockForUpdate()
                        ->first();

                    if ($slot && $slot->status === 'booked') {
                        $hasOtherActiveAppointment = Appointment::query()
                            ->where('time_slot_id', $slot->id)
                            ->whereIn('status', ['pending', 'confirmed'])
                            ->exists();

                        if (!$hasOtherActiveAppointment) {
                            $slot->status = 'available';
                            $slot->save();
                        }
                    }

                    return 'cancelled';
                });

                if ($result === 'cancelled') {
                    $cancelled++;
                } elseif ($result === 'confirmed') {
                    $confirmed++;
                } elseif ($result === 'waiting') {
                    $waiting++;
                } elseif ($result === 'stale_waiting') {
                    $waiting++;
                    $stalePayments++;
                }
            } catch (\Throwable $e) {
                Log::error('Expire pending appointment failed', [
                    'appointment_id' => $appointmentId,
                    'exception' => $e->getMessage(),
                ]);
            }
        }

        $summary = sprintf(
            'Expired appointment check completed. Cancelled: %d, confirmed: %d, awaiting payment verification: %d, stale payments requiring review: %d.',
            $cancelled,
            $confirmed,
            $waiting,
            $stalePayments
        );

        $this->info($summary);

        Log::info($summary);

        return self::SUCCESS;
    }
}
