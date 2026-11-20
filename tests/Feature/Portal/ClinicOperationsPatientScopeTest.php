<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\ClinicOperationsController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Mockery;
use Tests\TestCase;

final class ClinicOperationsPatientScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('name');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('role')->nullable();
            $table->unsignedSmallInteger('revenue_share_basis_points')->nullable();
            $table->string('status');
            $table->dateTime('joined_at')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_organization_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable();
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('clinic_organization_clinics', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('branch_code')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('doctor_settlements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('doctor_id');
            $table->timestamps();
        });

        Schema::create('clinic_staff_payrolls', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('staff_user_id');
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('title');
            $table->string('status');
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->timestamps();
        });

        Schema::create('patient_treatment_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('treatment_plan_id');
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('title');
            $table->text('clinical_notes')->nullable();
            $table->string('stage');
            $table->unsignedBigInteger('price')->default(0);
            $table->timestamps();
        });

        Schema::create('clinic_inventory_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('clinic_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->decimal('current_quantity', 12, 3)->default(0);
            $table->decimal('minimum_quantity', 12, 3)->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'clinic_inventory_items',
            'patient_treatment_items',
            'patient_treatment_plans',
            'patients',
            'clinic_staff_payrolls',
            'doctor_settlements',
            'clinic_organization_clinics',
            'clinic_organization_members',
            'clinic_organization_doctors',
            'clinic_organizations',
            'doctor_user',
            'addresses',
            'doctors',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_inventory_only_secretary_does_not_load_patient_treatment_plans(): void
    {
        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 77,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('addresses')->insert([
            'id' => 31,
            'doctor_id' => 11,
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_user')->insert([
            'doctor_id' => 11,
            'user_id' => 99,
            'permissions' => json_encode(['inventory.view']),
            'clinic_ids' => json_encode([31]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $patientId = DB::table('patients')->insertGetId([
            'doctor_id' => 11,
            'name' => 'Confidential Patient',
            'phone' => '09120000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $planId = DB::table('patient_treatment_plans')->insertGetId([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => $patientId,
            'title' => 'Confidential plan',
            'status' => 'active',
            'total_amount' => 100000,
            'discount_amount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('patient_treatment_items')->insert([
            'treatment_plan_id' => $planId,
            'sequence' => 1,
            'title' => 'Confidential treatment',
            'clinical_notes' => 'Do not expose to inventory-only staff',
            'stage' => 'planned',
            'price' => 10000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = 'secretary';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('clinic.operations.viewed', null, ['doctor_id' => 11, 'clinic_count' => 1]);

        $response = (new ClinicOperationsController())->index($audit);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertFalse(
            collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'patient_treatment_plans')),
            'Patient treatment plans must not be queried for a secretary with inventory-only permission.'
        );
    }
}
