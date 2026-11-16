<?php

namespace App\Actions\Portal\Fees;

use App\Models\Appointment;
use App\Models\Fee;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UpdateFeeAction
{
    /**
     * تعرفه‌ی جدید را ثبت می‌کند.
     *
     * @return bool true اگر تعرفه تغییر کرده، false اگر مبلغ همان قبلی بوده است.
     */
    public function handle(int $newFee): bool
    {
        $creatorId = Auth::guard('doctor')->id()
            ?? Auth::id();

        return DB::transaction(function () use ($newFee, $creatorId) {
            $activeFee = Fee::where('is_active', true)
                ->lockForUpdate()
                ->first();

            // مبلغ تغییر نکرده است
            if ($activeFee && (int) $activeFee->fee_rial === $newFee) {
                return false;
            }

            Fee::where('is_active', true)
                ->get()
                ->each(function (Fee $fee) {
                    $fee->is_active = false;
                    $fee->deactivated_at = now();
                    $fee->save();
                });

            Fee::create([
                'title' => 'تعرفه ویزیت',
                'fee_rial' => $newFee,
                'is_active' => true,
                'created_by' => $creatorId,
            ]);

            Appointment::updateUnpaidAppointmentsFee($newFee);

            return true;
        });
    }
}
