<?php

namespace App\Actions\Payments;

use App\Models\AppointmentPayment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListPaymentsRequiringReviewAction
{
    /**
     * Return only unresolved attempts that need operator reconciliation.
     * Patient identity/contact details are deliberately excluded.
     */
    public function handle(array $filters = []): LengthAwarePaginator
    {
        return AppointmentPayment::query()
            ->with(['appointment:id,patient_name,appointment_date,appointment_time,doctor_id,clinic_id,status'])
            ->whereIn('status', ['pending', 'initiated'])
            ->where(function ($query) {
                $query->where('gateway_result_code', 'REVIEW_REQUIRED')
                    ->orWhere(function ($stale) {
                        $stale->where('updated_at', '<=', now()->subMinutes(15))
                            ->where(function ($code) {
                                $code->whereNull('gateway_result_code')
                                    ->orWhere('gateway_result_code', '<>', 'REVIEW_REQUIRED');
                            });
                    });
            })
            ->when($filters['gateway'] ?? null, fn ($query, $gateway) => $query->where('gateway_name', $gateway))
            ->when($filters['from'] ?? null, fn ($query, $date) => $query->whereDate('updated_at', '>=', $date))
            ->when($filters['to'] ?? null, fn ($query, $date) => $query->whereDate('updated_at', '<=', $date))
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('payment_uuid', 'like', '%' . $search . '%')
                        ->orWhere('ref_num', 'like', '%' . $search . '%');

                    if (ctype_digit((string) $search)) {
                        $nested->orWhere('id', (int) $search)
                            ->orWhere('appointment_id', (int) $search);
                    }
                });
            })
            ->latest('updated_at')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (AppointmentPayment $payment) => [
                'id' => $payment->id,
                'appointment_id' => $payment->appointment_id,
                'payment_uuid' => $payment->payment_uuid,
                'gateway' => $payment->gateway_name,
                'status' => $payment->status,
                'amount_rial' => (int) $payment->amount_rial,
                'ref_num' => $payment->ref_num,
                'terminal_id' => $payment->terminal_id,
                'result_code' => $payment->gateway_result_code,
                'result_message' => $payment->gateway_result_message,
                'updated_at' => $payment->updated_at?->toIso8601String(),
                'appointment' => $payment->appointment ? [
                    'id' => $payment->appointment->id,
                    'patient_name' => $payment->appointment->patient_name,
                    'appointment_date' => $payment->appointment->appointment_date,
                    'appointment_time' => $payment->appointment->appointment_time,
                    'doctor_id' => $payment->appointment->doctor_id,
                    'clinic_id' => $payment->appointment->clinic_id,
                    'status' => $payment->appointment->status,
                ] : null,
            ]);
    }
}
