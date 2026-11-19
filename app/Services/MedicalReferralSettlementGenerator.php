<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MedicalCollaborationContract;
use App\Models\MedicalReferral;
use App\Models\MedicalReferralSettlement;
use Illuminate\Support\Facades\DB;

final class MedicalReferralSettlementGenerator
{
    /**
     * Create one payable line for each completed referral covered by an accepted,
     * currently valid contract. The unique referral_id makes this operation idempotent.
     */
    public function generateForDoctor(int $doctorId): int
    {
        return DB::transaction(function () use ($doctorId): int {
            $created = 0;
            $referrals = MedicalReferral::query()
                ->where('referring_doctor_id', $doctorId)
                ->where('status', 'completed')
                ->whereNotNull('completed_at')
                ->whereDoesntHave('settlement')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            foreach ($referrals as $referral) {
                $contract = MedicalCollaborationContract::query()
                    ->where('doctor_id', $doctorId)
                    ->where('provider_id', $referral->target_provider_id)
                    ->where('service_type', $referral->service_type)
                    ->where('status', 'active')
                    ->whereDate('starts_at', '<=', $referral->completed_at->toDateString())
                    ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $referral->completed_at->toDateString()))
                    ->orderByDesc('starts_at')
                    ->lockForUpdate()
                    ->first();

                if (!$contract || $contract->unit_fee_rial < 1) {
                    continue;
                }

                $settlement = MedicalReferralSettlement::query()->firstOrCreate(
                    ['referral_id' => $referral->id],
                    [
                        'contract_id' => $contract->id,
                        'amount_rial' => $contract->unit_fee_rial,
                        'status' => 'pending',
                    ]
                );
                if ($settlement->wasRecentlyCreated) {
                    $created++;
                }
            }

            return $created;
        }, 3);
    }
}
