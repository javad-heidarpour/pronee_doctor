<?php

namespace App\Actions\Portal\Fees;

use App\Models\Doctor;
use App\Models\Fee;

class ListFeesAction
{
    /**
     * @return array{activeFee: ?Fee, feeHistory: \Illuminate\Database\Eloquent\Collection, doctor: Doctor}
     */
    public function handle(Doctor $doctor): array
    {
        $fees = Fee::with('creator:id,name,caption,family')
            ->orderByDesc('id')
            ->get();

        $activeFee = $fees->first(
            fn (Fee $fee) => $fee->is_active
        );

        return [
            'activeFee' => $activeFee,
            'feeHistory' => $fees,
            'doctor' => $doctor,
        ];
    }
}