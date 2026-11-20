<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PatientAccountAccess;
use App\Models\PatientFollowUpTask;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;

final class PatientHealthAccessTest extends AuthTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('family')->nullable();
            $table->string('slug')->unique();
            $table->string('specialty_title')->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('national_code')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('status');
            $table->string('visit_number')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at');
            $table->string('priority');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('patient_account_accesses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('granted_by_user_id')->nullable();
            $table->string('access_type');
            $table->json('permissions')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_access_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('status');
            $table->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });

        Schema::create('security_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event', 100);
            $table->string('subject_type', 150)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function test_caregiver_without_summary_permission_cannot_open_the_patient_health_record(): void
    {
        [$user] = $this->createScenario(['view_appointments']);

        $this->actingAs($user, 'web')
            ->get(route('patient.health.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Patient/Health')
                ->where('hasAccess', false)
                ->where('accesses', [])
            );

        $this->assertDatabaseMissing('security_audit_logs', [
            'event' => 'patient.portal.health.viewed',
        ]);
    }

    public function test_inactive_patient_account_cannot_use_an_existing_authenticated_session(): void
    {
        [$user] = $this->createScenario(['view_summary', 'view_records']);
        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user, 'web')
            ->get(route('patient.health.index'))
            ->assertForbidden();

        $this->actingAs($user, 'web')
            ->post(route('patient.health.medications.store'), [])
            ->assertForbidden();

        $this->assertDatabaseMissing('security_audit_logs', [
            'event' => 'patient.portal.health.viewed',
        ]);
    }

    public function test_inactive_account_cannot_be_granted_caregiver_access(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'family' => 'Test',
            'email' => 'owner@example.test',
            'username' => 'owner-test',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $inactiveCaregiver = User::query()->create([
            'name' => 'Inactive',
            'family' => 'Caregiver',
            'email' => 'inactive-caregiver@example.test',
            'username' => 'inactive-caregiver',
            'password' => 'password',
            'role' => 'user',
            'is_active' => false,
        ]);
        $doctor = Doctor::query()->create([
            'name' => 'Doctor',
            'family' => 'Test',
            'slug' => 'doctor-owner-test',
        ]);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient',
        ]);
        PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => $owner->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);

        $this->actingAs($owner, 'web')
            ->post(route('patient.health.caregivers.store'), [
                'email' => $inactiveCaregiver->email,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('patient_account_accesses', [
            'patient_id' => $patient->id,
            'user_id' => $inactiveCaregiver->id,
        ]);
    }

    public function test_caregiver_receives_only_appointments_and_followups_explicitly_granted(): void
    {
        [$user, $access] = $this->createScenario([
            'view_summary',
            'view_appointments',
            'view_followups',
        ]);

        $this->actingAs($user, 'web')
            ->get(route('patient.health.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Patient/Health')
                ->where('hasAccess', true)
                ->has('patient.appointments', 1)
                ->has('followUps', 1)
                ->has('encounters', 0)
                ->has('availablePrescriptions', 0)
                ->has('carePathways', 0)
            );

        $this->assertDatabaseHas('security_audit_logs', [
            'event' => 'patient.portal.health.viewed',
            'subject_type' => PatientAccountAccess::class,
            'subject_id' => $access->id,
            'metadata' => null,
        ]);
    }

    public function test_caregiver_without_appointment_or_followup_permissions_receives_neither_collection(): void
    {
        [$user] = $this->createScenario(['view_summary']);

        $this->actingAs($user, 'web')
            ->get(route('patient.health.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Patient/Health')
                ->where('hasAccess', true)
                ->has('patient.appointments', 0)
                ->has('followUps', 0)
                ->has('encounters', 0)
            );
    }

    /**
     * @return array{0: User, 1: PatientAccountAccess}
     */
    private function createScenario(array $permissions): array
    {
        $user = User::query()->create([
            'name' => 'Caregiver',
            'family' => 'Test',
            'email' => 'caregiver@example.test',
            'username' => 'caregiver-test',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);

        $doctor = Doctor::query()->create([
            'name' => 'Doctor',
            'family' => 'Test',
            'slug' => 'doctor-test',
            'specialty_title' => 'General',
        ]);

        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient',
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Appointment::query()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'clinic_id' => $clinicId,
            'status' => 'confirmed',
            'appointment_date' => now()->toDateString(),
            'appointment_time' => '10:00:00',
            'visit_number' => 'V-100',
        ]);

        PatientFollowUpTask::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'patient_id' => $patient->id,
            'title' => 'Follow-up reminder',
            'description' => 'Follow-up description',
            'due_at' => now()->addDay(),
            'priority' => 'normal',
            'status' => 'pending',
        ]);

        $access = PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => $user->id,
            'access_type' => 'caregiver',
            'permissions' => $permissions,
        ]);

        return [$user, $access];
    }
}
