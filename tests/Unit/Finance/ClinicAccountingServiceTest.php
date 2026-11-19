<?php

declare(strict_types=1);

namespace Tests\Unit\Finance;

use App\Models\ClinicJournalEntry;
use App\Models\PatientInstallmentPayment;
use App\Models\PatientTreatmentInstallment;
use App\Models\PatientTreatmentItem;
use App\Services\Finance\ClinicAccountingService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class ClinicAccountingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('clinic_financial_accounts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('code');
            $table->string('scope_key')->unique();
            $table->string('name');
            $table->string('type');
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clinic_journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('reference')->nullable();
            $table->string('description');
            $table->dateTime('occurred_at');
            $table->string('status')->default('posted');
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('clinic_journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id');
            $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_treatment_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('treatment_plan_id');
            $table->string('stage');
            $table->dateTime('performed_at')->nullable();
            $table->unsignedBigInteger('recognized_amount')->default(0);
            $table->unsignedBigInteger('paid_applied_amount')->default(0);
            $table->unsignedBigInteger('price')->default(0);
            $table->timestamps();
        });

        Schema::create('patient_treatment_installments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('treatment_plan_id');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });

        Schema::create('patient_installment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('installment_id');
            $table->unsignedBigInteger('amount');
            $table->timestamps();
        });
    }

    public function test_patient_advance_is_allocated_to_revenue_and_remaining_balance_becomes_receivable(): void
    {
        $installment = PatientTreatmentInstallment::query()->create(['treatment_plan_id' => 77, 'amount' => 1000]);
        $payment = PatientInstallmentPayment::query()->create(['installment_id' => $installment->id, 'amount' => 1000]);

        $accounting = app(ClinicAccountingService::class);
        $accounting->recordPatientPayment(9, 3, 12, 5, $payment->id, 77, 1000, 'پیش‌پرداخت', now()->toDateTimeString());
        $accounting->recordPatientPayment(9, 3, 12, 5, $payment->id, 77, 1000, 'پیش‌پرداخت تکراری', now()->toDateTimeString());
        $this->assertSame(1, ClinicJournalEntry::query()->where('idempotency_key', 'patient-payment:' . $payment->id)->count());

        $item = PatientTreatmentItem::query()->create([
            'treatment_plan_id' => 77, 'stage' => 'completed', 'price' => 1500,
        ]);
        $entry = $accounting->recordTreatmentRevenue(
            9, 3, 12, 5, $item->id, 77, 1500, 'عصب‌کشی', now()->toDateTimeString()
        );

        $this->assertSame(1000, (int) $item->fresh()->paid_applied_amount);
        $this->assertSame(1500, (int) $item->fresh()->recognized_amount);
        $this->assertSame(1500, (int) $entry->lines()->sum('debit'));
        $this->assertSame(1500, (int) $entry->lines()->sum('credit'));
        $this->assertSame(1, ClinicJournalEntry::query()->where('idempotency_key', 'treatment-revenue:' . $item->id)->count());
    }

    public function test_later_payment_is_applied_to_completed_treatment_receivable_first(): void
    {
        $item = PatientTreatmentItem::query()->create([
            'treatment_plan_id' => 88, 'stage' => 'completed', 'price' => 900,
            'recognized_amount' => 900, 'paid_applied_amount' => 0,
        ]);
        $installment = PatientTreatmentInstallment::query()->create(['treatment_plan_id' => 88, 'amount' => 900]);
        $payment = PatientInstallmentPayment::query()->create(['installment_id' => $installment->id, 'amount' => 600]);

        $entry = app(ClinicAccountingService::class)->recordPatientPayment(
            9, 3, 12, 5, $payment->id, 88, 600, 'دریافت قسط', now()->toDateTimeString()
        );

        $this->assertSame(600, (int) $item->fresh()->paid_applied_amount);
        $this->assertSame(600, (int) $entry->lines()->sum('debit'));
        $this->assertSame(600, (int) $entry->lines()->sum('credit'));
        $this->assertSame(0, (int) $entry->lines()->whereHas('account', fn ($q) => $q->where('code', '2100'))->sum('credit'));
    }

    public function test_completed_treatment_details_are_immutable_but_financial_allocation_is_allowed(): void
    {
        $item = PatientTreatmentItem::query()->create([
            'treatment_plan_id' => 99, 'stage' => 'completed', 'price' => 500,
            'recognized_amount' => 500, 'paid_applied_amount' => 0,
        ]);

        $item->update(['paid_applied_amount' => 250]);
        $this->assertSame(250, (int) $item->fresh()->paid_applied_amount);

        try {
            $item->update(['price' => 600]);
            $this->fail('Completed treatment prices must not be mutable.');
        } catch (ValidationException) {
            $this->assertSame(250, (int) $item->fresh()->paid_applied_amount);
        }
    }

    public function test_inventory_purchase_is_booked_to_supplier_payable_not_cash(): void
    {
        $entry = app(ClinicAccountingService::class)->recordInventoryMovement(
            9, 3, 12, 5, 987, 'purchase_credit', 2500, 'دریافت اعتباری کالا', now()->toDateTimeString()
        );

        $this->assertSame(2500, (int) $entry->lines()->whereHas('account', fn ($q) => $q->where('code', '1300'))->sum('debit'));
        $this->assertSame(2500, (int) $entry->lines()->whereHas('account', fn ($q) => $q->where('code', '2400'))->sum('credit'));
        $this->assertSame(0, (int) $entry->lines()->whereHas('account', fn ($q) => $q->where('code', '1000'))->sum('credit'));
    }

    public function test_journal_rejects_unbalanced_entries(): void
    {
        $this->expectException(ValidationException::class);

        app(ClinicAccountingService::class)->post(
            9, 3, 12, 5, 'test', 1, 'سند نامتوازن', now()->toDateTimeString(),
            'test-unbalanced', [
                ['code' => '1000', 'debit' => 100, 'credit' => 0],
                ['code' => '1100', 'debit' => 0, 'credit' => 90],
            ]
        );
    }
}
