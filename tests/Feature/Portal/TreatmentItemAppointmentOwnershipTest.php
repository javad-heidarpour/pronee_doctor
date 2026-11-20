<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\ClinicOperationsController;
use App\Models\Doctor;
use App\Models\PatientTreatmentItem;
use App\Models\PatientTreatmentPlan;
use App\Models\User;
use App\Services\Finance\ClinicAccountingService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class TreatmentItemAppointmentOwnershipTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('national_code')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('patient_treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('total_amount')->default(0);
            $table->unsignedBigInteger('discount_amount')->default(0);
            $table->timestamps();
        });

        Schema::create('patient_treatment_items', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('treatment_plan_id');
            $table->unsignedBigInteger('service_id')->nullable();
            $table->unsignedBigInteger('provider_doctor_id')->nullable();
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->string('item_type')->default('procedure');
            $table->string('title');
            $table->string('tooth_number')->nullable();
            $table->string('tooth_surface')->nullable();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('stage')->default('planned');
            $table->unsignedBigInteger('price')->default(0);
            $table->unsignedBigInteger('recognized_amount')->default(0);
            $table->unsignedBigInteger('paid_applied_amount')->default(0);
            $table->unsignedSmallInteger('doctor_share_basis_points')->nullable();
            $table->unsignedSmallInteger('follow_up_after_days')->nullable();
            $table->dateTime('performed_at')->nullable();
            $table->dateTime('follow_up_due_at')->nullable();
            $table->text('clinical_notes')->nullable();
            $table->unsignedBigInteger('completed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('status');
        });
    }

    protected function tearDown(): void
    {
        foreach (['clinic_organizations', 'patient_treatment_items', 'patient_treatment_plans', 'appointments', 'patients', 'doctors'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_treatment_item_cannot_be_linked_to_another_patients_appointment(): void
    {
        $this->assertAppointmentCannotBeLinked(42);
    }

    public function test_treatment_item_cannot_be_linked_to_legacy_appointment_without_patient_id(): void
    {
        $this->assertAppointmentCannotBeLinked(null);
    }

    private function assertAppointmentCannotBeLinked(?int $appointmentPatientId): void
    {
        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 99,
            'name' => 'Test Doctor',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('patients')->insert([
            ['id' => 41, 'doctor_id' => 11, 'name' => 'Plan patient', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 42, 'doctor_id' => 11, 'name' => 'Different patient', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('appointments')->insert([
            'id' => 502,
            'doctor_id' => 11,
            'patient_id' => $appointmentPatientId,
            'clinic_id' => 31,
            'status' => 'confirmed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $plan = PatientTreatmentPlan::query()->create([
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => 41,
            'total_amount' => 100000,
            'discount_amount' => 0,
        ]);
        $item = PatientTreatmentItem::query()->create([
            'treatment_plan_id' => $plan->id,
            'title' => 'Treatment for plan patient',
            'item_type' => 'procedure',
            'sequence' => 1,
            'stage' => 'planned',
            'price' => 10000,
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        Auth::guard('web')->setUser($user);

        $doctor = new Doctor();
        $doctor->id = 11;

        $controller = new class extends ClinicOperationsController {
            public Doctor $testDoctor;

            protected function currentDoctor(): Doctor
            {
                return $this->testDoctor;
            }

            protected function authorizeSecretaryPermission(string $permission, ?Doctor $doctor = null): void
            {
            }

            protected function accessibleClinicIds(?Doctor $doctor = null): array
            {
                return [31];
            }
        };
        $controller->testDoctor = $doctor;
        app()->instance(ClinicAccountingService::class, \Mockery::mock(ClinicAccountingService::class));

        try {
            $controller->updateTreatmentItem(
                Request::create('/portal/operations/treatment-items/'.$item->id, 'PUT', [
                    'stage' => 'scheduled',
                    'appointment_id' => 502,
                ]),
                $item->id,
                app(ClinicAccountingService::class)
            );

            $this->fail('An appointment belonging to another patient must be rejected.');
        } catch (ModelNotFoundException) {
            $this->assertNull($item->fresh()->appointment_id);
            $this->assertSame('planned', $item->fresh()->stage);
        }
    }
}
