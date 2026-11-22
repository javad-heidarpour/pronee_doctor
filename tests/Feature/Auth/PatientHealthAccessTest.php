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

        Schema::create('patient_health_observations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('user_id');
            $table->string('measurement_type', 32);
            $table->decimal('value', 10, 2);
            $table->string('unit', 16);
            $table->timestamp('observed_at');
            $table->string('note', 500)->nullable();
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
        $ownerAccess = PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => $owner->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);

        $this->actingAs($owner, 'web')
            ->post(route('patient.health.caregivers.store'), [
                'access_id' => $ownerAccess->id,
                'email' => $inactiveCaregiver->email,
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('patient_account_accesses', [
            'patient_id' => $patient->id,
            'user_id' => $inactiveCaregiver->id,
        ]);
    }

    public function test_granting_caregiver_does_not_downgrade_an_existing_owner_access(): void
    {
        $owner = User::query()->create([
            'name' => 'Owner',
            'family' => 'Test',
            'email' => 'owner-grant@example.test',
            'username' => 'owner-grant-test',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $existingOwner = User::query()->create([
            'name' => 'Existing',
            'family' => 'Owner',
            'email' => 'existing-owner@example.test',
            'username' => 'existing-owner-test',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $doctor = Doctor::query()->create([
            'name' => 'Doctor',
            'family' => 'Test',
            'slug' => 'doctor-owner-grant-test',
        ]);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient',
        ]);

        $permissions = ['view_summary', 'view_records', 'view_appointments', 'view_followups', 'manage_caregivers'];

        $ownerAccess = PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => $owner->id,
            'access_type' => 'owner',
            'permissions' => $permissions,
        ]);
        $existingOwnerAccess = PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => $existingOwner->id,
            'access_type' => 'owner',
            'permissions' => $permissions,
        ]);

        $this->actingAs($owner, 'web')
            ->post(route('patient.health.caregivers.store'), [
                'access_id' => $ownerAccess->id,
                'email' => $existingOwner->email,
            ])
            ->assertStatus(409);

        $this->assertSame('owner', $existingOwnerAccess->fresh()->access_type);
        $this->assertSame($permissions, $existingOwnerAccess->fresh()->permissions);
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

    public function test_patient_can_switch_between_their_active_summary_access_grants(): void
    {
        [$user] = $this->createScenario(['view_summary', 'view_appointments']);

        $secondDoctor = Doctor::query()->create([
            'name' => 'Second Doctor',
            'family' => 'Test',
            'slug' => 'doctor-second-health-access',
            'specialty_title' => 'Internal Medicine',
        ]);
        $secondPatient = Patient::query()->create([
            'doctor_id' => $secondDoctor->id,
            'name' => 'Second Patient',
        ]);
        $secondAccess = PatientAccountAccess::query()->create([
            'patient_id' => $secondPatient->id,
            'user_id' => $user->id,
            'access_type' => 'caregiver',
            'permissions' => ['view_summary'],
        ]);

        $this->actingAs($user, 'web')
            ->get(route('patient.health.index', ['access_id' => $secondAccess->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Patient/Health')
                ->where('hasAccess', true)
                ->where('selectedAccessId', $secondAccess->id)
                ->where('patient.id', $secondPatient->id)
                ->where('patient.name', 'Second Patient')
                ->has('accesses', 2)
            );

        $this->assertDatabaseHas('security_audit_logs', [
            'event' => 'patient.portal.health.viewed',
            'subject_type' => PatientAccountAccess::class,
            'subject_id' => $secondAccess->id,
        ]);
    }

    public function test_patient_cannot_select_a_foreign_or_non_summary_access_grant(): void
    {
        [$user, $ownAccess] = $this->createScenario(['view_summary']);

        $hiddenPatient = Patient::query()->create([
            'doctor_id' => $ownAccess->patient->doctor_id,
            'name' => 'Hidden Patient',
        ]);
        $hiddenAccess = PatientAccountAccess::query()->create([
            'patient_id' => $hiddenPatient->id,
            'user_id' => $user->id,
            'access_type' => 'caregiver',
            'permissions' => ['view_records'],
        ]);

        $foreignUser = User::query()->create([
            'name' => 'Foreign',
            'family' => 'User',
            'email' => 'foreign-health-access@example.test',
            'username' => 'foreign-health-access',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $foreignAccess = PatientAccountAccess::query()->create([
            'patient_id' => $ownAccess->patient_id,
            'user_id' => $foreignUser->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);

        $this->actingAs($user, 'web')
            ->get(route('patient.health.index', ['access_id' => $hiddenAccess->id]))
            ->assertNotFound();

        $this->get(route('patient.health.index', ['access_id' => $foreignAccess->id]))
            ->assertNotFound();

        $this->assertDatabaseMissing('security_audit_logs', [
            'event' => 'patient.portal.health.viewed',
        ]);
    }

    public function test_owner_observation_is_written_to_the_explicitly_selected_patient_record(): void
    {
        $user = User::query()->create([
            'name' => 'Multi Record Owner',
            'family' => 'Test',
            'email' => 'multi-owner-observation@example.test',
            'username' => 'multi-owner-observation',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);

        $firstDoctor = Doctor::query()->create([
            'name' => 'First Doctor',
            'slug' => 'multi-owner-first-doctor',
        ]);
        $secondDoctor = Doctor::query()->create([
            'name' => 'Second Doctor',
            'slug' => 'multi-owner-second-doctor',
        ]);
        $firstPatient = Patient::query()->create([
            'doctor_id' => $firstDoctor->id,
            'name' => 'First Patient Record',
        ]);
        $secondPatient = Patient::query()->create([
            'doctor_id' => $secondDoctor->id,
            'name' => 'Second Patient Record',
        ]);

        PatientAccountAccess::query()->create([
            'patient_id' => $firstPatient->id,
            'user_id' => $user->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);
        $selectedAccess = PatientAccountAccess::query()->create([
            'patient_id' => $secondPatient->id,
            'user_id' => $user->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);

        $this->actingAs($user, 'web')
            ->post(route('patient.health.observations.store'), [
                'access_id' => $selectedAccess->id,
                'measurement_type' => 'weight',
                'value' => 72.5,
                'observed_at' => now()->subHour()->toDateTimeString(),
                'note' => 'Before breakfast',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_health_observations', [
            'patient_id' => $secondPatient->id,
            'user_id' => $user->id,
            'measurement_type' => 'weight',
        ]);
        $this->assertDatabaseMissing('patient_health_observations', [
            'patient_id' => $firstPatient->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_owner_cannot_submit_an_observation_for_another_users_access_grant(): void
    {
        $user = User::query()->create([
            'name' => 'Owner',
            'family' => 'Test',
            'email' => 'owner-foreign-access@example.test',
            'username' => 'owner-foreign-access',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $foreignUser = User::query()->create([
            'name' => 'Foreign Owner',
            'family' => 'Test',
            'email' => 'foreign-owner-access@example.test',
            'username' => 'foreign-owner-access',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $doctor = Doctor::query()->create([
            'name' => 'Foreign Access Doctor',
            'slug' => 'foreign-access-doctor',
        ]);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Foreign Access Patient',
        ]);
        $foreignAccess = PatientAccountAccess::query()->create([
            'patient_id' => $patient->id,
            'user_id' => $foreignUser->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);

        $this->actingAs($user, 'web')
            ->post(route('patient.health.observations.store'), [
                'access_id' => $foreignAccess->id,
                'measurement_type' => 'weight',
                'value' => 70,
                'observed_at' => now()->subHour()->toDateTimeString(),
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('patient_health_observations', 0);
    }

    public function test_observation_deletion_uses_the_patient_bound_to_the_observation_not_the_first_owner_grant(): void
    {
        $user = User::query()->create([
            'name' => 'Multi Record Deleter',
            'family' => 'Test',
            'email' => 'multi-owner-delete@example.test',
            'username' => 'multi-owner-delete',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $firstDoctor = Doctor::query()->create([
            'name' => 'First Doctor',
            'slug' => 'multi-owner-delete-first',
        ]);
        $secondDoctor = Doctor::query()->create([
            'name' => 'Second Doctor',
            'slug' => 'multi-owner-delete-second',
        ]);
        $firstPatient = Patient::query()->create([
            'doctor_id' => $firstDoctor->id,
            'name' => 'First Patient',
        ]);
        $secondPatient = Patient::query()->create([
            'doctor_id' => $secondDoctor->id,
            'name' => 'Second Patient',
        ]);
        PatientAccountAccess::query()->create([
            'patient_id' => $firstPatient->id,
            'user_id' => $user->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);
        PatientAccountAccess::query()->create([
            'patient_id' => $secondPatient->id,
            'user_id' => $user->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);
        $observationId = DB::table('patient_health_observations')->insertGetId([
            'patient_id' => $secondPatient->id,
            'user_id' => $user->id,
            'measurement_type' => 'weight',
            'value' => 71,
            'unit' => 'kg',
            'observed_at' => now()->subHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user, 'web')
            ->delete(route('patient.health.observations.destroy', $observationId))
            ->assertRedirect();

        $this->assertDatabaseMissing('patient_health_observations', ['id' => $observationId]);
    }

    public function test_caregiver_grant_targets_the_selected_owner_record_when_multiple_are_linked(): void
    {
        $owner = User::query()->create([
            'name' => 'Multi Record Owner',
            'family' => 'Test',
            'email' => 'multi-owner-caregiver@example.test',
            'username' => 'multi-owner-caregiver',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $caregiver = User::query()->create([
            'name' => 'Caregiver',
            'family' => 'Test',
            'email' => 'selected-caregiver@example.test',
            'username' => 'selected-caregiver',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $firstDoctor = Doctor::query()->create([
            'name' => 'First Doctor',
            'slug' => 'multi-owner-caregiver-first',
        ]);
        $secondDoctor = Doctor::query()->create([
            'name' => 'Second Doctor',
            'slug' => 'multi-owner-caregiver-second',
        ]);
        $firstPatient = Patient::query()->create([
            'doctor_id' => $firstDoctor->id,
            'name' => 'First Patient',
        ]);
        $secondPatient = Patient::query()->create([
            'doctor_id' => $secondDoctor->id,
            'name' => 'Second Patient',
        ]);
        PatientAccountAccess::query()->create([
            'patient_id' => $firstPatient->id,
            'user_id' => $owner->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);
        $selectedAccess = PatientAccountAccess::query()->create([
            'patient_id' => $secondPatient->id,
            'user_id' => $owner->id,
            'access_type' => 'owner',
            'permissions' => [],
        ]);

        $this->actingAs($owner, 'web')
            ->post(route('patient.health.caregivers.store'), [
                'access_id' => $selectedAccess->id,
                'email' => $caregiver->email,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_account_accesses', [
            'patient_id' => $secondPatient->id,
            'user_id' => $caregiver->id,
            'access_type' => 'caregiver',
        ]);
        $this->assertDatabaseMissing('patient_account_accesses', [
            'patient_id' => $firstPatient->id,
            'user_id' => $caregiver->id,
        ]);
    }

    public function test_access_request_is_created_when_exactly_one_patient_identity_matches(): void
    {
        $user = User::query()->create([
            'name' => 'Access Request User',
            'family' => 'Test',
            'email' => 'access-request-single@example.test',
            'username' => 'access-request-single',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $doctor = Doctor::query()->create([
            'name' => 'Access Request Doctor',
            'slug' => 'access-request-single-doctor',
        ]);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Verified Patient Candidate',
            'phone' => '09120000000',
            'national_code' => '0012345678',
        ]);

        $this->actingAs($user, 'web')
            ->post(route('patient.health.access.request'), [
                'national_code' => '0012345678',
                'phone' => '09120000000',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('patient_access_requests', [
            'user_id' => $user->id,
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'pending',
        ]);
    }

    public function test_access_request_does_not_choose_arbitrarily_between_duplicate_patient_identity_matches(): void
    {
        $user = User::query()->create([
            'name' => 'Ambiguous Access User',
            'family' => 'Test',
            'email' => 'access-request-ambiguous@example.test',
            'username' => 'access-request-ambiguous',
            'password' => 'password',
            'role' => 'user',
            'is_active' => true,
        ]);
        $firstDoctor = Doctor::query()->create([
            'name' => 'First Candidate Doctor',
            'slug' => 'access-request-ambiguous-first',
        ]);
        $secondDoctor = Doctor::query()->create([
            'name' => 'Second Candidate Doctor',
            'slug' => 'access-request-ambiguous-second',
        ]);

        foreach ([$firstDoctor, $secondDoctor] as $doctor) {
            Patient::query()->create([
                'doctor_id' => $doctor->id,
                'name' => 'Duplicate Identity Candidate',
                'phone' => '09120000000',
                'national_code' => '0012345678',
            ]);
        }

        $this->actingAs($user, 'web')
            ->post(route('patient.health.access.request'), [
                'national_code' => '0012345678',
                'phone' => '09120000000',
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('patient_access_requests', 0);
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
