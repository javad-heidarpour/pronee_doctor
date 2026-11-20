<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\ClinicOperationsController;
use App\Models\ClinicOrganization;
use App\Models\Doctor;
use App\Models\DoctorSettlement;
use App\Models\User;
use App\Services\Finance\ClinicAccountingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ClinicOperationsSettlementWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name'); $table->string('family')->nullable();
            $table->string('email')->unique(); $table->string('password')->nullable();
            $table->string('role')->default('user'); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable(); $table->string('family')->nullable();
            $table->string('slug')->nullable(); $table->boolean('is_active')->default(true);
            $table->timestamps(); $table->softDeletes();
        });

        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('owner_user_id');
            $table->string('name'); $table->string('slug'); $table->string('timezone')->nullable();
            $table->string('status')->default('active'); $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('doctor_id');
            $table->string('role')->default('practitioner');
            $table->unsignedInteger('revenue_share_basis_points')->default(7000);
            $table->string('status')->default('active'); $table->date('joined_at')->nullable();
            $table->timestamps(); $table->primary(['organization_id', 'doctor_id']);
        });

        Schema::create('patient_treatment_plans', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('doctor_id'); $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('patient_id')->nullable(); $table->unsignedBigInteger('created_by')->nullable();
            $table->string('title')->nullable(); $table->string('specialty_module')->nullable();
            $table->string('status')->default('active'); $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0); $table->unsignedBigInteger('deposit_amount')->default(0);
            $table->unsignedInteger('installment_count')->default(1); $table->text('clinical_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_treatment_items', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('treatment_plan_id');
            $table->unsignedBigInteger('service_id')->nullable(); $table->unsignedBigInteger('provider_doctor_id');
            $table->unsignedBigInteger('appointment_id')->nullable(); $table->string('item_type')->nullable();
            $table->string('title')->nullable(); $table->unsignedInteger('sequence')->default(1);
            $table->string('stage')->default('planned'); $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('recognized_amount')->default(0); $table->unsignedBigInteger('paid_applied_amount')->default(0);
            $table->unsignedInteger('doctor_share_basis_points')->nullable(); $table->timestamp('performed_at')->nullable();
            $table->timestamp('follow_up_due_at')->nullable(); $table->text('clinical_notes')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable(); $table->timestamps();
        });

        Schema::create('doctor_settlements', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id'); $table->unsignedBigInteger('doctor_id');
            $table->date('period_start'); $table->date('period_end');
            $table->unsignedBigInteger('gross_amount'); $table->unsignedBigInteger('clinic_share_amount');
            $table->bigInteger('adjustment_amount')->default(0); $table->unsignedBigInteger('net_amount');
            $table->string('status')->default('draft'); $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('paid_by')->nullable(); $table->timestamp('paid_at')->nullable();
            $table->string('payment_method')->nullable(); $table->string('payment_reference')->nullable();
            $table->text('notes')->nullable(); $table->timestamps();
            $table->unique(['organization_id', 'doctor_id', 'period_start', 'period_end'], 'doctor_settlement_period_unique');
        });

        Schema::create('clinic_financial_accounts', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('clinic_id')->nullable(); $table->string('code');
            $table->string('scope_key')->unique(); $table->string('name'); $table->string('type');
            $table->boolean('is_system')->default(true); $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clinic_journal_entries', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('clinic_id')->nullable(); $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable(); $table->string('source_type');
            $table->unsignedBigInteger('source_id'); $table->string('reference')->nullable();
            $table->string('description'); $table->timestamp('occurred_at'); $table->string('status');
            $table->string('idempotency_key')->unique(); $table->timestamps();
        });

        Schema::create('clinic_journal_lines', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('journal_entry_id');
            $table->unsignedBigInteger('account_id'); $table->unsignedBigInteger('debit')->default(0);
            $table->unsignedBigInteger('credit')->default(0); $table->string('memo')->nullable();
            $table->timestamps();
        });
    }

    public function test_owner_can_create_one_draft_settlement_using_item_level_share_then_approve_and_pay_it(): void
    {
        [$owner, $provider, $ownerDoctor, $providerDoctor, $organization, $planId] = $this->scenario();
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $ownerDoctor->id]);

        app(ClinicOperationsController::class)->createSettlement($this->request([
            'organization_id' => $organization->id,
            'doctor_id' => $providerDoctor->id,
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
        ]));

        $settlement = DoctorSettlement::query()->firstOrFail();
        $this->assertSame(3000000, (int) $settlement->gross_amount);
        $this->assertSame(1150000, (int) $settlement->clinic_share_amount);
        $this->assertSame(1850000, (int) $settlement->net_amount);
        $this->assertSame('draft', $settlement->status);

        app(ClinicOperationsController::class)->createSettlement($this->request([
            'organization_id' => $organization->id,
            'doctor_id' => $providerDoctor->id,
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
        ]));
        $this->assertSame(1, DoctorSettlement::query()->count(), 'The same organization/doctor/period must not create duplicate settlements.');

        app(ClinicOperationsController::class)->approveSettlement($settlement->id);
        $settlement->refresh();
        $this->assertSame('approved', $settlement->status);
        $this->assertSame($owner->id, (int) $settlement->approved_by);

        app(ClinicOperationsController::class)->paySettlement(
            $this->request(['payment_method' => 'cash', 'payment_reference' => 'CASH-SETTLEMENT-1']),
            $settlement->id,
            app(ClinicAccountingService::class)
        );

        $settlement->refresh();
        $this->assertSame('paid', $settlement->status);
        $this->assertSame('cash', $settlement->payment_method);
        $this->assertSame('CASH-SETTLEMENT-1', $settlement->payment_reference);
        $this->assertSame($owner->id, (int) $settlement->paid_by);
        $this->assertNotNull($settlement->paid_at);

        $entry = DB::table('clinic_journal_entries')->where('idempotency_key', 'doctor-settlement:'.$settlement->id)->first();
        $this->assertNotNull($entry);
        $lines = DB::table('clinic_journal_lines')->where('journal_entry_id', $entry->id)->get();
        $this->assertCount(2, $lines);
        $this->assertSame(1850000, (int) $lines->sum('debit'));
        $this->assertSame(1850000, (int) $lines->sum('credit'));
    }

    public function test_non_owner_doctor_cannot_approve_another_organizations_settlement(): void
    {
        [$owner, $provider, $ownerDoctor, $providerDoctor, $organization] = $this->scenario();
        $settlement = DoctorSettlement::query()->create([
            'organization_id' => $organization->id,
            'doctor_id' => $providerDoctor->id,
            'period_start' => '2026-11-01',
            'period_end' => '2026-11-30',
            'gross_amount' => 3000000,
            'clinic_share_amount' => 1150000,
            'adjustment_amount' => 0,
            'net_amount' => 1850000,
            'status' => 'draft',
        ]);
        Auth::guard('web')->setUser($provider);
        session(['active_doctor_id' => $providerDoctor->id]);

        $this->expectException(ModelNotFoundException::class);
        app(ClinicOperationsController::class)->approveSettlement($settlement->id);
    }

    private function scenario(): array
    {
        $owner = User::query()->create([
            'name' => 'Organization Owner', 'email' => 'owner@example.test',
            'role' => 'doctor', 'is_active' => true,
        ]);
        $provider = User::query()->create([
            'name' => 'Provider Doctor', 'email' => 'provider@example.test',
            'role' => 'doctor', 'is_active' => true,
        ]);
        $ownerDoctor = Doctor::query()->create([
            'user_id' => $owner->id, 'name' => 'Owner', 'family' => 'Doctor',
            'slug' => 'owner-doctor', 'is_active' => true,
        ]);
        $providerDoctor = Doctor::query()->create([
            'user_id' => $provider->id, 'name' => 'Provider', 'family' => 'Doctor',
            'slug' => 'provider-doctor', 'is_active' => true,
        ]);
        $organization = ClinicOrganization::query()->create([
            'owner_user_id' => $owner->id, 'name' => 'Test Organization',
            'slug' => 'test-organization', 'timezone' => 'Asia/Tehran', 'status' => 'active',
        ]);
        DB::table('clinic_organization_doctors')->insert([
            [
                'organization_id' => $organization->id, 'doctor_id' => $ownerDoctor->id,
                'role' => 'owner', 'revenue_share_basis_points' => 10000, 'status' => 'active',
                'joined_at' => '2026-01-01', 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'organization_id' => $organization->id, 'doctor_id' => $providerDoctor->id,
                'role' => 'practitioner', 'revenue_share_basis_points' => 6500, 'status' => 'active',
                'joined_at' => '2026-01-01', 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
        $planId = DB::table('patient_treatment_plans')->insertGetId([
            'organization_id' => $organization->id, 'doctor_id' => $ownerDoctor->id,
            'title' => 'November treatment plan', 'status' => 'active',
            'total_amount' => 3000000, 'discount_amount' => 0, 'deposit_amount' => 0,
            'installment_count' => 1, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('patient_treatment_items')->insert([
            [
                'treatment_plan_id' => $planId, 'provider_doctor_id' => $providerDoctor->id,
                'title' => 'Service A', 'sequence' => 1, 'stage' => 'completed',
                'price' => 2000000, 'recognized_amount' => 2000000, 'paid_applied_amount' => 0,
                'doctor_share_basis_points' => 6000, 'performed_at' => '2026-11-10 10:00:00',
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'treatment_plan_id' => $planId, 'provider_doctor_id' => $providerDoctor->id,
                'title' => 'Service B', 'sequence' => 2, 'stage' => 'completed',
                'price' => 1000000, 'recognized_amount' => 1000000, 'paid_applied_amount' => 0,
                'doctor_share_basis_points' => null, 'performed_at' => '2026-11-12 10:00:00',
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);

        return [$owner, $provider, $ownerDoctor, $providerDoctor, $organization, $planId];
    }

    private function request(array $data): Request
    {
        return Request::create(
            '/portal/clinic-operations/settlements',
            'POST',
            $data,
            [],
            [],
            ['HTTP_REFERER' => '/portal/clinic-operations/settlements']
        );
    }
}
