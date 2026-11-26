<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Models\PatientMedicationPlan;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PatientMedicationPlanClinicScopeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('patient_encounters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id');
            $table->timestamps();
        });

        Schema::create('patient_encounter_prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->timestamps();
        });

        Schema::create('patient_medication_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('prescription_id');
            $table->date('start_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_medication_plans');
        Schema::dropIfExists('patient_encounter_prescriptions');
        Schema::dropIfExists('patient_encounters');

        parent::tearDown();
    }

    public function test_medication_plans_are_limited_to_matching_patient_encounters_in_accessible_clinics(): void
    {
        DB::table('patient_encounters')->insert([
            ['id' => 11, 'doctor_id' => 7, 'patient_id' => 41, 'clinic_id' => 31, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 12, 'doctor_id' => 7, 'patient_id' => 41, 'clinic_id' => 32, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 13, 'doctor_id' => 8, 'patient_id' => 42, 'clinic_id' => 31, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('patient_encounter_prescriptions')->insert([
            ['id' => 101, 'encounter_id' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 102, 'encounter_id' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 103, 'encounter_id' => 13, 'created_at' => now(), 'updated_at' => now()],
        ]);

        DB::table('patient_medication_plans')->insert([
            ['id' => 201, 'patient_id' => 41, 'user_id' => 90, 'prescription_id' => 101, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 202, 'patient_id' => 41, 'user_id' => 90, 'prescription_id' => 102, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 203, 'patient_id' => 42, 'user_id' => 91, 'prescription_id' => 103, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
            // A mismatched plan/encounter patient must not inherit access through either identifier.
            ['id' => 204, 'patient_id' => 99, 'user_id' => 92, 'prescription_id' => 101, 'status' => 'active', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $ids = PatientMedicationPlan::query()
            ->forAccessibleClinics(7, [31, 31])
            ->where('patient_id', 41)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([201], $ids);
    }

    public function test_medication_plan_scope_fails_closed_without_accessible_clinics(): void
    {
        DB::table('patient_encounters')->insert([
            'id' => 11,
            'doctor_id' => 7,
            'patient_id' => 41,
            'clinic_id' => 31,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('patient_encounter_prescriptions')->insert([
            'id' => 101,
            'encounter_id' => 11,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('patient_medication_plans')->insert([
            'id' => 201,
            'patient_id' => 41,
            'user_id' => 90,
            'prescription_id' => 101,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ids = PatientMedicationPlan::query()
            ->forAccessibleClinics(7, [])
            ->pluck('id')
            ->all();

        $this->assertSame([], $ids);
    }
}
