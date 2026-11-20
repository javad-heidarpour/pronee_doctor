<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\Patient360Controller;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class Patient360ControllerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->json('base_permissions')->nullable();
            $table->json('base_clinic_ids')->nullable();
            $table->boolean('managed_by_organization')->default(false);
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('national_code')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('status');
            $table->unsignedBigInteger('visit_number')->nullable();
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->unsignedBigInteger('visit_reason_id')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_encounters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('diagnosis')->nullable();
            $table->text('assessment')->nullable();
            $table->text('plan')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('patient_encounter_prescriptions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('encounter_id');
            $table->string('medication_name')->nullable();
            $table->string('strength')->nullable();
            $table->string('dosage')->nullable();
            $table->string('route')->nullable();
            $table->string('frequency')->nullable();
            $table->unsignedSmallInteger('duration_days')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('patient_clinical_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('kind');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_problems', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('encounter_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->string('category', 32);
            $table->string('title', 180);
            $table->string('code', 64)->nullable();
            $table->string('code_system', 32);
            $table->text('description')->nullable();
            $table->string('status', 20);
            $table->string('severity', 20)->nullable();
            $table->date('onset_date')->nullable();
            $table->date('resolved_on')->nullable();
            $table->string('source', 32);
            $table->timestamps();
        });

        Schema::create('patient_vital_signs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('measurement_type');
            $table->decimal('value', 10, 2);
            $table->string('unit');
            $table->dateTime('measured_at');
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at')->nullable();
            $table->string('status');
            $table->string('priority')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_care_pathways', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('title');
            $table->string('goal')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('patient_care_pathway_steps', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('pathway_id');
            $table->string('title');
            $table->string('status');
            $table->dateTime('due_at')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('patient_medication_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('prescription_id');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
        });
    }

    public function test_doctor_360_uses_true_totals_and_keeps_latest_vital_for_every_type(): void
    {
        [$doctor, $patient, $clinicId] = $this->doctorPatientAndClinic();

        for ($i = 0; $i < 31; $i++) {
            DB::table('appointments')->insert([
                'doctor_id' => $doctor,
                'patient_id' => $patient,
                'clinic_id' => $clinicId,
                'appointment_date' => now()->subDays($i)->toDateString(),
                'appointment_time' => '09:00:00',
                'status' => 'visited',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('patient_encounters')->insert([
            'doctor_id' => $doctor,
            'patient_id' => $patient,
            'clinic_id' => $clinicId,
            'diagnosis' => 'تشخیص',
            'assessment' => 'ارزیابی',
            'status' => 'signed',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('patient_clinical_records')->insert([
            'doctor_id' => $doctor,
            'patient_id' => $patient,
            'kind' => 'allergy',
            'title' => 'حساسیت ثبت‌شده',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('patient_problems')->insert([
            [
                'doctor_id' => $doctor,
                'patient_id' => $patient,
                'category' => 'diagnosis',
                'title' => 'دیابت نوع ۲',
                'code' => 'E11.9',
                'code_system' => 'icd10',
                'status' => 'active',
                'severity' => 'moderate',
                'source' => 'clinician_assessed',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => $doctor,
                'patient_id' => $patient,
                'category' => 'symptom',
                'title' => 'سردرد قبلی',
                'code' => null,
                'code_system' => 'local',
                'status' => 'resolved',
                'severity' => null,
                'source' => 'patient_reported',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        for ($i = 0; $i < 51; $i++) {
            DB::table('patient_vital_signs')->insert([
                'doctor_id' => $doctor,
                'patient_id' => $patient,
                'measurement_type' => 'blood_pressure',
                'value' => 120 + $i,
                'unit' => 'mmHg',
                'measured_at' => now()->subMinutes($i),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('patient_vital_signs')->insert([
            'doctor_id' => $doctor,
            'patient_id' => $patient,
            'measurement_type' => 'weight',
            'value' => 75,
            'unit' => 'kg',
            'measured_at' => now()->subYear(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->authenticateDoctor($doctor);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        $response = (new Patient360Controller())->show(
            Patient::query()->findOrFail($patient),
            $audit
        );

        $this->assertInstanceOf(Response::class, $response);
        $props = $this->responseProps($response);

        $this->assertSame(31, $props['summary']['appointments']);
        $this->assertCount(30, $props['appointments']);
        $this->assertSame(1, $props['summary']['encounters']);
        $this->assertSame(1, $props['summary']['active_allergies']);
        $this->assertSame(1, $props['summary']['active_problems']);
        $this->assertCount(2, $props['problems']);
        $this->assertSame('E11.9', collect($props['problems'])->firstWhere('title', 'دیابت نوع ۲')['code']);
        $this->assertCount(50, $props['vitals']);
        $this->assertNotNull(collect($props['latestVitals'])->firstWhere('type', 'weight'));
        $this->assertTrue($props['canViewClinical']);
    }

    public function test_secretary_without_clinical_permission_only_sees_appointments_in_assigned_clinics(): void
    {
        [$doctorId, $patientId, $clinicId, $otherClinicId] = $this->doctorPatientAndClinic(true);

        foreach ([$clinicId, $otherClinicId] as $id) {
            DB::table('appointments')->insert([
                'doctor_id' => $doctorId,
                'patient_id' => $patientId,
                'clinic_id' => $id,
                'appointment_date' => now()->toDateString(),
                'appointment_time' => '10:00:00',
                'status' => 'visited',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $secretary = DB::table('users')->insertGetId([
            'name' => 'Secretary',
            'family' => 'Test',
            'username' => 'secretary-test',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_user')->insert([
            'doctor_id' => $doctorId,
            'user_id' => $secretary,
            'permissions' => json_encode(['patients.view']),
            'clinic_ids' => json_encode([$clinicId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::guard('web')->setUser(User::query()->findOrFail($secretary));
        session(['active_doctor_id' => $doctorId]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        $response = (new Patient360Controller())->show(
            Patient::query()->findOrFail($patientId),
            $audit
        );
        $props = $this->responseProps($response);

        $this->assertFalse($props['canViewClinical']);
        $this->assertCount(1, $props['appointments']);
        $this->assertSame(1, $props['summary']['appointments']);
        $this->assertSame(0, $props['summary']['active_allergies']);
        $this->assertSame(0, $props['summary']['active_problems']);
        $this->assertCount(0, $props['problems']);
        $this->assertCount(0, $props['clinicalRecords']);
        $this->assertCount(0, $props['vitals']);
        $this->assertCount(0, $props['encounters']);
    }

    public function test_secretary_without_patient_view_permission_is_forbidden(): void
    {
        [$doctorId, $patientId, $clinicId] = $this->doctorPatientAndClinic(true);
        $secretary = DB::table('users')->insertGetId([
            'name' => 'No Access Secretary',
            'family' => 'Test',
            'username' => 'no-access-secretary',
            'password' => bcrypt('password'),
            'role' => 'secretary',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_user')->insert([
            'doctor_id' => $doctorId,
            'user_id' => $secretary,
            'permissions' => json_encode([]),
            'clinic_ids' => json_encode([$clinicId]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::guard('web')->setUser(User::query()->findOrFail($secretary));
        session(['active_doctor_id' => $doctorId]);

        $this->expectException(HttpException::class);

        (new Patient360Controller())->show(
            Patient::query()->findOrFail($patientId),
            Mockery::mock(RecordSecurityAuditEventAction::class)
        );
    }


    private function responseProps(Response $response): array
    {
        $request = Request::create('/portal/patients/1/360', 'GET');
        $request->headers->set('X-Inertia', 'true');
        $httpResponse = $response->toResponse($request);
        $payload = json_decode($httpResponse->getContent(), true, 512, JSON_THROW_ON_ERROR);

        return $payload['props'];
    }

    private function doctorPatientAndClinic(bool $secondClinic = false): array
    {
        $ownerId = DB::table('users')->insertGetId([
            'name' => 'Doctor',
            'family' => 'Owner',
            'username' => 'doctor-owner',
            'password' => bcrypt('password'),
            'role' => 'doctor',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $doctorId = DB::table('doctors')->insertGetId([
            'user_id' => $ownerId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $clinicId = (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'Clinic One',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $otherClinicId = (int) DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'Clinic Two',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $patientId = (int) DB::table('patients')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'Patient 360',
            'phone' => '09120000000',
            'national_code' => '0012345678',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $secondClinic
            ? [$doctorId, $patientId, $clinicId, $otherClinicId]
            : [$doctorId, $patientId, $clinicId];
    }

    private function authenticateDoctor(int $doctorId): void
    {
        $userId = (int) DB::table('doctors')->where('id', $doctorId)->value('user_id');
        Auth::guard('web')->setUser(User::query()->findOrFail($userId));
        session(['active_doctor_id' => $doctorId]);
    }
}
