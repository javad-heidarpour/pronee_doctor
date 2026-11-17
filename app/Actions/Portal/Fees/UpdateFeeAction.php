<?php

namespace App\Actions\Portal\Fees;

use App\Models\Appointment;
use App\Models\Fee;
use Illuminate\Support\Facades\DB;

class UpdateFeeAction
{
    /**
     * تعرفه‌ی جدید را ثبت می‌کند.
     *
     * @return bool true اگر تعرفه تغییر کرده، false اگر مبلغ همان قبلی بوده است.
     */
    public function handle(int $newFee, int $doctorId): bool
    {
        return DB::transaction(function () use ($newFee, $doctorId) {
            // قفل رکورد پزشک حتی زمانی که هنوز تعرفه فعالی وجود ندارد؛
            // قفل کردن صرفاً ردیف Fee در حالت نبود ردیف، جلوی ثبت هم‌زمان
            // دو تعرفه فعال را در همه موتورهای پایگاه داده نمی‌گیرد.
            Doctor::query()
                ->whereKey($doctorId)
                ->lockForUpdate()
                ->firstOrFail();

            $activeFee = Fee::where('created_by', $doctorId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if ($activeFee && (int) $activeFee->fee_rial === $newFee) {
                return false;
            }

            Fee::where('created_by', $doctorId)
                ->where('is_active', true)
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
                'created_by' => $doctorId,
            ]);

            Appointment::updateUnpaidAppointmentsFee($newFee, $doctorId);

            return true;
        });
    }
}