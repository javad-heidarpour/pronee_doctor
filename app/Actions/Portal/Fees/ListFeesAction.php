<?php

namespace App\Actions\Portal\Fees;

use App\Models\Fee;
use Illuminate\Support\Facades\Auth;

class ListFeesAction
{
    /**
     * @return array{activeFee: ?Fee, feeHistory: \Illuminate\Database\Eloquent\Collection, doctor: mixed}
     */
    public function handle(): array
    {
        $fees = Fee::with('creator:id,name,caption,family')
            ->orderByDesc('id')
            ->get();

        $activeFee = $fees->first(
            fn (Fee $fee) => $fee->is_active
        );

        $doctor = Auth::guard('web')->user()->doctorProfile;

        return [
            'activeFee' => $activeFee,
            'feeHistory' => $fees,
            'doctor' => $doctor,
        ];
    }
}
