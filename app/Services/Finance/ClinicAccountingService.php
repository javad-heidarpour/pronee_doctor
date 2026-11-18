<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\ClinicFinancialAccount;
use App\Models\ClinicJournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClinicAccountingService
{
    private const ACCOUNTS = [
        '1000' => ['name' => 'صندوق و بانک', 'type' => 'asset'],
        '1100' => ['name' => 'مطالبات از بیماران', 'type' => 'asset'],
        '1300' => ['name' => 'موجودی مواد و کالا', 'type' => 'asset'],
        '2100' => ['name' => 'پیش‌دریافت بیماران', 'type' => 'liability'],
        '2200' => ['name' => 'بدهی به پزشکان', 'type' => 'liability'],
        '2300' => ['name' => 'بدهی حقوق کارکنان', 'type' => 'liability'],
        '4000' => ['name' => 'درآمد خدمات درمانی', 'type' => 'income'],
        '5000' => ['name' => 'هزینه‌های عملیاتی', 'type' => 'expense'],
        '5010' => ['name' => 'هزینه مواد مصرفی', 'type' => 'expense'],
        '5100' => ['name' => 'هزینه سهم پزشکان', 'type' => 'expense'],
        '5200' => ['name' => 'هزینه حقوق کارکنان', 'type' => 'expense'],
    ];

    public function recordPatientPayment(
        ?int $organizationId, int $clinicId, int $doctorId, int $userId,
        int $paymentId, int $amount, string $description, string $occurredAt
    ): ClinicJournalEntry {
        return $this->post($organizationId, $clinicId, $doctorId, $userId,
            'patient_installment_payment', $paymentId, 'دریافت بیمار: ' . $description,
            $occurredAt, 'patient-payment:' . $paymentId,
            [['code' => '1000', 'debit' => $amount, 'credit' => 0],
             ['code' => '1100', 'debit' => 0, 'credit' => $amount]]);
    }

    public function recordExpense(
        ?int $organizationId, int $clinicId, int $doctorId, int $userId,
        int $expenseId, int $amount, string $description, string $occurredAt
    ): ClinicJournalEntry {
        return $this->post($organizationId, $clinicId, $doctorId, $userId,
            'clinic_expense', $expenseId, $description, $occurredAt,
            'clinic-expense:' . $expenseId,
            [['code' => '5000', 'debit' => $amount, 'credit' => 0],
             ['code' => '1000', 'debit' => 0, 'credit' => $amount]]);
    }

    public function recordTreatmentRevenue(
        ?int $organizationId, int $clinicId, int $doctorId, int $userId,
        int $treatmentItemId, int $amount, string $description, string $occurredAt
    ): ClinicJournalEntry {
        return $this->post($organizationId, $clinicId, $doctorId, $userId,
            'patient_treatment_item', $treatmentItemId, 'شناسایی درآمد: ' . $description,
            $occurredAt, 'treatment-revenue:' . $treatmentItemId,
            [['code' => '2100', 'debit' => $amount, 'credit' => 0],
             ['code' => '4000', 'debit' => 0, 'credit' => $amount]]);
    }

    public function recordInventoryMovement(
        ?int $organizationId, int $clinicId, int $doctorId, int $userId,
        int $movementId, string $movementType, int $totalCost, string $description, string $occurredAt
    ): ?ClinicJournalEntry {
        if ($totalCost < 1 || $movementType === 'adjustment') return null;
        $lines = in_array($movementType, ['purchase', 'return'], true)
            ? [['code' => '1300', 'debit' => $totalCost, 'credit' => 0], ['code' => '1000', 'debit' => 0, 'credit' => $totalCost]]
            : [['code' => '5010', 'debit' => $totalCost, 'credit' => 0], ['code' => '1300', 'debit' => 0, 'credit' => $totalCost]];
        return $this->post($organizationId, $clinicId, $doctorId, $userId,
            'clinic_inventory_movement', $movementId, $description, $occurredAt,
            'inventory-movement:' . $movementId, $lines);
    }

    public function post(
        ?int $organizationId, ?int $clinicId, ?int $doctorId, int $userId,
        string $sourceType, int $sourceId, string $description, string $occurredAt,
        string $idempotencyKey, array $lines
    ): ClinicJournalEntry {
        return DB::transaction(function () use (
            $organizationId, $clinicId, $doctorId, $userId, $sourceType, $sourceId,
            $description, $occurredAt, $idempotencyKey, $lines
        ): ClinicJournalEntry {
            $existing = ClinicJournalEntry::query()->where('idempotency_key', $idempotencyKey)->first();
            if ($existing) return $existing->load('lines.account');

            $normalized = [];
            $debits = 0;
            $credits = 0;
            foreach ($lines as $line) {
                $debit = (int) ($line['debit'] ?? 0);
                $credit = (int) ($line['credit'] ?? 0);
                if ($debit < 0 || $credit < 0 || ($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0)) {
                    throw ValidationException::withMessages(['finance' => 'ردیف سند حسابداری نامعتبر است.']);
                }
                $debits += $debit;
                $credits += $credit;
                $normalized[] = ['code' => (string) $line['code'], 'debit' => $debit, 'credit' => $credit];
            }
            if ($debits < 1 || $debits !== $credits) {
                throw ValidationException::withMessages(['finance' => 'سند حسابداری باید تراز باشد و جمع بدهکار و بستانکار برابر باشد.']);
            }

            $entry = ClinicJournalEntry::create([
                'organization_id' => $organizationId, 'clinic_id' => $clinicId,
                'doctor_id' => $doctorId, 'created_by' => $userId,
                'source_type' => $sourceType, 'source_id' => $sourceId,
                'description' => mb_substr($description, 0, 240),
                'occurred_at' => $occurredAt, 'status' => 'posted',
                'idempotency_key' => $idempotencyKey,
            ]);

            foreach ($normalized as $line) {
                $account = ClinicFinancialAccount::query()->firstOrCreate(
                    ['organization_id' => $organizationId, 'clinic_id' => $clinicId, 'code' => $line['code']],
                    ['name' => self::ACCOUNTS[$line['code']]['name'], 'type' => self::ACCOUNTS[$line['code']]['type'], 'is_system' => true, 'is_active' => true]
                );
                $entry->lines()->create([
                    'account_id' => $account->id,
                    'debit' => $line['debit'], 'credit' => $line['credit'],
                ]);
            }

            return $entry->load('lines.account');
        }, 3);
    }
}
