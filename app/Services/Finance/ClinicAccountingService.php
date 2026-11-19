<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Models\ClinicFinancialAccount;
use App\Models\ClinicJournalEntry;
use App\Models\PatientInstallmentPayment;
use App\Models\PatientTreatmentItem;
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
        '2310' => ['name' => 'مطالبات مساعده کارکنان', 'type' => 'asset'],
        '2320' => ['name' => 'مطالبات وام کارکنان', 'type' => 'asset'],
        '2330' => ['name' => 'سایر کسورات پرداختنی کارکنان', 'type' => 'liability'],
        '2400' => ['name' => 'حساب‌های پرداختنی تأمین‌کنندگان', 'type' => 'liability'],
        '3000' => ['name' => 'سرمایه و مانده افتتاحیه', 'type' => 'equity'],
        '4000' => ['name' => 'درآمد خدمات درمانی', 'type' => 'income'],
        '4100' => ['name' => 'سود تعدیلات موجودی', 'type' => 'income'],
        '5000' => ['name' => 'هزینه‌های عملیاتی', 'type' => 'expense'],
        '5010' => ['name' => 'هزینه مواد مصرفی', 'type' => 'expense'],
        '5100' => ['name' => 'هزینه سهم پزشکان', 'type' => 'expense'],
        '5200' => ['name' => 'هزینه حقوق کارکنان', 'type' => 'expense'],
    ];

    public function recordPatientPayment(
        ?int $organizationId, int $clinicId, int $doctorId, int $userId,
        int $paymentId, int $planId, int $amount, string $description, string $occurredAt
    ): ClinicJournalEntry {
        return DB::transaction(function () use ($organizationId, $clinicId, $doctorId, $userId, $paymentId, $planId, $amount, $description, $occurredAt): ClinicJournalEntry {
            $key = 'patient-payment:' . $paymentId;
            $existing = ClinicJournalEntry::query()->where('idempotency_key', $key)->first();
            if ($existing) return $existing->load('lines.account');

            $remaining = $amount;
            $receivableApplied = 0;
            $items = PatientTreatmentItem::query()->where('treatment_plan_id', $planId)
                ->where('stage', 'completed')->whereColumn('paid_applied_amount', '<', 'recognized_amount')
                ->orderBy('performed_at')->lockForUpdate()->get();
            foreach ($items as $item) {
                if ($remaining < 1) break;
                $outstanding = max(0, (int) $item->recognized_amount - (int) $item->paid_applied_amount);
                $applied = min($remaining, $outstanding);
                if ($applied > 0) {
                    $item->increment('paid_applied_amount', $applied);
                    $remaining -= $applied;
                    $receivableApplied += $applied;
                }
            }

            $lines = [['code' => '1000', 'debit' => $amount, 'credit' => 0]];
            if ($receivableApplied > 0) $lines[] = ['code' => '1100', 'debit' => 0, 'credit' => $receivableApplied];
            if ($remaining > 0) $lines[] = ['code' => '2100', 'debit' => 0, 'credit' => $remaining];

            return $this->post($organizationId, $clinicId, $doctorId, $userId,
                'patient_installment_payment', $paymentId, 'دریافت بیمار: ' . $description,
                $occurredAt, $key, $lines);
        }, 3);
    }

    public function recordAppointmentPayment(?int $organizationId, int $clinicId, int $doctorId, ?int $userId, int $paymentId, int $amount, string $description, string $occurredAt): ClinicJournalEntry
    {
        if ($amount < 1) throw ValidationException::withMessages(['finance' => 'مبلغ پرداخت نوبت معتبر نیست.']);
        return $this->post($organizationId, $clinicId, $doctorId, $userId, 'appointment_payment', $paymentId,
            'دریافت آنلاین نوبت: ' . $description, $occurredAt, 'appointment-payment:' . $paymentId,
            [['code' => '1000', 'debit' => $amount, 'credit' => 0], ['code' => '4000', 'debit' => 0, 'credit' => $amount]]);
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
        int $treatmentItemId, int $planId, int $amount, string $description, string $occurredAt
    ): ClinicJournalEntry {
        return DB::transaction(function () use ($organizationId, $clinicId, $doctorId, $userId, $treatmentItemId, $planId, $amount, $description, $occurredAt): ClinicJournalEntry {
            $key = 'treatment-revenue:' . $treatmentItemId;
            $existing = ClinicJournalEntry::query()->where('idempotency_key', $key)->first();
            if ($existing) return $existing->load('lines.account');

            $item = PatientTreatmentItem::query()->whereKey($treatmentItemId)
                ->where('treatment_plan_id', $planId)->lockForUpdate()->firstOrFail();
            if ((int) $item->recognized_amount > 0) {
                throw ValidationException::withMessages(['finance' => 'درآمد این مرحله قبلاً شناسایی شده است.']);
            }

            $collected = (int) PatientInstallmentPayment::query()
                ->whereHas('installment', fn ($q) => $q->where('treatment_plan_id', $planId))
                ->sum('amount');
            $alreadyApplied = (int) PatientTreatmentItem::query()
                ->where('treatment_plan_id', $planId)->sum('paid_applied_amount');
            $availableAdvance = max(0, $collected - $alreadyApplied);
            $advanceApplied = min($amount, $availableAdvance);
            $receivableCreated = $amount - $advanceApplied;

            $item->update([
                'recognized_amount' => $amount,
                'paid_applied_amount' => $advanceApplied,
            ]);

            $lines = [];
            if ($advanceApplied > 0) $lines[] = ['code' => '2100', 'debit' => $advanceApplied, 'credit' => 0];
            if ($receivableCreated > 0) $lines[] = ['code' => '1100', 'debit' => $receivableCreated, 'credit' => 0];
            $lines[] = ['code' => '4000', 'debit' => 0, 'credit' => $amount];

            return $this->post($organizationId, $clinicId, $doctorId, $userId,
                'patient_treatment_item', $treatmentItemId, 'شناسایی درآمد: ' . $description,
                $occurredAt, $key, $lines);
        }, 3);
    }

    public function recordInventoryMovement(
        ?int $organizationId, int $clinicId, int $doctorId, int $userId,
        int $movementId, string $movementType, int $totalCost, string $description, string $occurredAt
    ): ?ClinicJournalEntry {
        if ($totalCost < 1) return null;
        $lines = match ($movementType) {
            'opening' => [['code' => '1300', 'debit' => $totalCost, 'credit' => 0], ['code' => '3000', 'debit' => 0, 'credit' => $totalCost]],
            'purchase' => [['code' => '1300', 'debit' => $totalCost, 'credit' => 0], ['code' => '2400', 'debit' => 0, 'credit' => $totalCost]],
            'return' => [['code' => '1300', 'debit' => $totalCost, 'credit' => 0], ['code' => '5010', 'debit' => 0, 'credit' => $totalCost]],
            'adjustment_in' => [['code' => '1300', 'debit' => $totalCost, 'credit' => 0], ['code' => '4100', 'debit' => 0, 'credit' => $totalCost]],
            'adjustment_out' => [['code' => '5010', 'debit' => $totalCost, 'credit' => 0], ['code' => '1300', 'debit' => 0, 'credit' => $totalCost]],
            default => [['code' => '5010', 'debit' => $totalCost, 'credit' => 0], ['code' => '1300', 'debit' => 0, 'credit' => $totalCost]],
        };
        return $this->post($organizationId, $clinicId, $doctorId, $userId,
            'clinic_inventory_movement', $movementId, $description, $occurredAt,
            'inventory-movement:' . $movementId, $lines);
    }

    public function post(
        ?int $organizationId, ?int $clinicId, ?int $doctorId, ?int $userId,
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
                $code = (string) ($line['code'] ?? '');
                if (! isset(self::ACCOUNTS[$code])) throw ValidationException::withMessages(['finance' => 'کد حسابداری تعریف‌نشده است: ' . $code]);
                $debits += $debit;
                $credits += $credit;
                $normalized[] = ['code' => $code, 'debit' => $debit, 'credit' => $credit];
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
                $scopeKey = ($organizationId ?? 0) . ':' . ($clinicId ?? 0) . ':' . $line['code'];
                $account = ClinicFinancialAccount::query()->firstOrCreate(
                    ['scope_key' => $scopeKey],
                    [
                        'organization_id' => $organizationId, 'clinic_id' => $clinicId,
                        'code' => $line['code'], 'name' => self::ACCOUNTS[$line['code']]['name'],
                        'type' => self::ACCOUNTS[$line['code']]['type'], 'is_system' => true, 'is_active' => true,
                    ]
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
