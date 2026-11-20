<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\ClinicOrganizationController;
use App\Models\ClinicOrganization;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ClinicOrganizationIndexAuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('slug')->nullable();
            $table->string('specialty_title')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->string('full_address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->text('permissions')->nullable();
            $table->text('clinic_ids')->nullable();
            $table->text('base_permissions')->nullable();
            $table->text('base_clinic_ids')->nullable();
            $table->boolean('managed_by_organization')->default(false);
            $table->timestamps();
            $table->unique(['doctor_id', 'user_id']);
        });

        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('name');
            $table->string('slug');
            $table->string('legal_name')->nullable();
            $table->string('registration_number')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('timezone')->nullable();
            $table->string('status')->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('role')->default('practitioner');
            $table->integer('revenue_share_basis_points')->default(0);
            $table->string('status')->default('active');
            $table->date('joined_at')->nullable();
            $table->timestamps();
            $table->primary(['organization_id', 'doctor_id']);
        });

        Schema::create('clinic_organization_clinics', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('branch_code')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->primary(['organization_id', 'clinic_id']);
        });

        Schema::create('clinic_organization_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable();
            $table->text('permissions')->nullable();
            $table->text('clinic_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->primary(['organization_id', 'user_id']);
        });
    }

    public function test_admin_can_view_all_organizations_without_being_an_owner_or_member(): void
    {
        $admin = $this->user('admin');
        $doctor = $this->user('doctor');
        $doctorProfileId = $this->doctorProfile($doctor->id);

        $firstOrganization = $this->organization($doctor->id, 'Organization One');
        $secondOrganization = $this->organization($doctor->id, 'Organization Two');
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $firstOrganization,
            'doctor_id' => $doctorProfileId,
            'role' => 'owner',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::guard('web')->setUser($admin);

        $response = app(ClinicOrganizationController::class)->index();
        $page = $response->toResponse(Request::create('/portal/organizations', 'GET', [], [], [], ['HTTP_X_INERTIA' => 'true']))->getData(true);
        $organizationIds = collect($page['props']['organizations'])->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertEqualsCanonicalizing([$firstOrganization, $secondOrganization], $organizationIds);
        $this->assertTrue($page['props']['isAdmin']);
    }

    public function test_doctor_can_only_view_organizations_they_own_or_belong_to(): void
    {
        $doctor = $this->user('doctor');
        $otherDoctor = $this->user('doctor');
        $doctorProfileId = $this->doctorProfile($doctor->id);
        $otherDoctorProfileId = $this->doctorProfile($otherDoctor->id);

        $accessibleOrganization = $this->organization($otherDoctor->id, 'Accessible Organization');
        $unrelatedOrganization = $this->organization($otherDoctor->id, 'Unrelated Organization');

        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $accessibleOrganization,
            'doctor_id' => $doctorProfileId,
            'role' => 'practitioner',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $unrelatedOrganization,
            'doctor_id' => $otherDoctorProfileId,
            'role' => 'owner',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Auth::guard('web')->setUser($doctor);

        $response = app(ClinicOrganizationController::class)->index();
        $page = $response->toResponse(Request::create('/portal/organizations', 'GET', [], [], [], ['HTTP_X_INERTIA' => 'true']))->getData(true);
        $organizationIds = collect($page['props']['organizations'])->pluck('id')->map(fn ($id) => (int) $id)->all();

        $this->assertSame([$accessibleOrganization], $organizationIds);
        $this->assertFalse($page['props']['isAdmin']);
    }

    public function test_admin_cannot_mutate_an_organization_from_the_read_only_overview(): void
    {
        $admin = $this->user('admin');
        $owner = $this->user('doctor');
        $organizationId = $this->organization($owner->id, 'Read Only Organization');

        Auth::guard('web')->setUser($admin);

        try {
            app(ClinicOrganizationController::class)->inviteDoctor(
                Request::create('/portal/organizations/' . $organizationId . '/doctors', 'POST'),
                ClinicOrganization::query()->findOrFail($organizationId)
            );
            $this->fail('Administrators must not mutate organization memberships.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_secretary_cannot_open_organization_overview(): void
    {
        Auth::guard('web')->setUser($this->user('secretary'));

        try {
            app(ClinicOrganizationController::class)->index();
            $this->fail('Secretaries must not access the organization overview.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_doctor_can_create_an_organization_and_attach_only_an_owned_clinic(): void
    {
        $owner = $this->user('doctor');
        $doctorId = $this->doctorProfile($owner->id);
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId, 'name' => 'Owned clinic', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($owner);

        app(ClinicOrganizationController::class)->store(Request::create('/portal/organizations', 'POST', [
            'name' => 'Test Organization',
            'clinic_ids' => [$clinicId],
        ]));

        $organization = DB::table('clinic_organizations')->where('owner_user_id', $owner->id)->first();
        $this->assertNotNull($organization);
        $this->assertDatabaseHas('clinic_organization_doctors', [
            'organization_id' => $organization->id, 'doctor_id' => $doctorId, 'role' => 'owner', 'status' => 'active',
        ]);
        $this->assertDatabaseHas('clinic_organization_clinics', [
            'organization_id' => $organization->id, 'clinic_id' => $clinicId, 'status' => 'active',
        ]);
        $this->assertDatabaseHas('addresses', ['id' => $clinicId, 'organization_id' => $organization->id]);
    }

    public function test_doctor_cannot_create_an_organization_with_another_doctors_clinic(): void
    {
        $owner = $this->user('doctor');
        $other = $this->user('doctor');
        $otherDoctorId = $this->doctorProfile($other->id);
        $foreignClinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $otherDoctorId, 'name' => 'Foreign clinic', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($owner);

        try {
            app(ClinicOrganizationController::class)->store(Request::create('/portal/organizations', 'POST', [
                'name' => 'Invalid Organization', 'clinic_ids' => [$foreignClinicId],
            ]));
            $this->fail('A doctor must not attach a clinic owned by another doctor.');
        } catch (\Illuminate\Validation\ValidationException $exception) {
            $this->assertArrayHasKey('clinic_ids', $exception->errors());
        }

        $this->assertDatabaseMissing('clinic_organizations', ['name' => 'Invalid Organization']);
    }

    public function test_admin_cannot_create_an_organization(): void
    {
        Auth::guard('web')->setUser($this->user('admin'));

        try {
            app(ClinicOrganizationController::class)->store(Request::create('/portal/organizations', 'POST', ['name' => 'Admin Organization']));
            $this->fail('Organization creation is restricted to doctor owners.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_owner_can_invite_an_active_doctor_and_invitation_starts_pending(): void
    {
        $owner = $this->user('doctor');
        $ownerDoctorId = $this->doctorProfile($owner->id);
        $invitee = $this->user('doctor');
        $inviteeDoctorId = $this->doctorProfile($invitee->id);
        $organizationId = $this->organization($owner->id, 'Membership Organization');
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $organizationId, 'doctor_id' => $ownerDoctorId,
            'role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($owner);

        app(ClinicOrganizationController::class)->inviteDoctor(
            Request::create('/portal/organizations/'.$organizationId.'/doctors', 'POST', [
                'doctor_id' => $inviteeDoctorId, 'revenue_share_basis_points' => 6500, 'role' => 'practitioner',
            ]),
            ClinicOrganization::query()->findOrFail($organizationId)
        );

        $this->assertDatabaseHas('clinic_organization_doctors', [
            'organization_id' => $organizationId, 'doctor_id' => $inviteeDoctorId,
            'status' => 'pending', 'revenue_share_basis_points' => 6500,
        ]);
    }

    public function test_invited_doctor_can_accept_membership_but_uninvited_doctor_cannot(): void
    {
        $owner = $this->user('doctor');
        $ownerDoctorId = $this->doctorProfile($owner->id);
        $invitee = $this->user('doctor');
        $inviteeDoctorId = $this->doctorProfile($invitee->id);
        $organizationId = $this->organization($owner->id, 'Accept Organization');
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $organizationId, 'doctor_id' => $ownerDoctorId,
            'role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $organizationId, 'doctor_id' => $inviteeDoctorId,
            'role' => 'practitioner', 'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($invitee);

        app(ClinicOrganizationController::class)->acceptDoctor(ClinicOrganization::query()->findOrFail($organizationId));

        $this->assertDatabaseHas('clinic_organization_doctors', [
            'organization_id' => $organizationId, 'doctor_id' => $inviteeDoctorId, 'status' => 'active',
        ]);
    }

    public function test_owner_can_attach_and_detach_an_active_member_clinic_without_deleting_it(): void
    {
        $owner = $this->user('doctor');
        $doctorId = $this->doctorProfile($owner->id);
        $organizationId = $this->organization($owner->id, 'Branches Organization');
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $organizationId, 'doctor_id' => $doctorId,
            'role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId, 'name' => 'Branch A', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($owner);
        $organization = ClinicOrganization::query()->findOrFail($organizationId);

        app(ClinicOrganizationController::class)->attachClinic(
            Request::create('/portal/organizations/'.$organizationId.'/clinics', 'POST', ['clinic_id' => $clinicId, 'branch_code' => 'A1']),
            $organization
        );

        $this->assertDatabaseHas('addresses', ['id' => $clinicId, 'organization_id' => $organizationId]);

        app(ClinicOrganizationController::class)->detachClinic($organization, $clinicId);

        $this->assertDatabaseHas('clinic_organization_clinics', [
            'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'status' => 'inactive',
        ]);
        $this->assertDatabaseHas('addresses', ['id' => $clinicId, 'organization_id' => null]);
    }

    public function test_owner_can_invite_active_secretary_with_scoped_permissions_and_clinics(): void
    {
        $owner = $this->user('doctor');
        $doctorId = $this->doctorProfile($owner->id);
        $organizationId = $this->organization($owner->id, 'Staff Organization');
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $organizationId, 'doctor_id' => $doctorId,
            'role' => 'owner', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId, 'organization_id' => $organizationId, 'name' => 'Staff clinic',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_clinics')->insert([
            'organization_id' => $organizationId, 'clinic_id' => $clinicId,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $secretary = $this->user('secretary');
        Auth::guard('web')->setUser($owner);

        app(ClinicOrganizationController::class)->inviteMember(
            Request::create('/portal/organizations/'.$organizationId.'/members', 'POST', [
                'user_id' => $secretary->id, 'role' => 'receptionist',
                'permissions' => ['appointments.view', 'patients.view'],
                'clinic_ids' => [$clinicId],
            ]),
            ClinicOrganization::query()->findOrFail($organizationId)
        );

        $this->assertDatabaseHas('clinic_organization_members', [
            'organization_id' => $organizationId, 'user_id' => $secretary->id,
            'role' => 'receptionist', 'is_active' => true,
        ]);
    }

    private function user(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role),
            'email' => $role . '-' . uniqid() . '@example.test',
            'role' => $role,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }

    private function doctorProfile(int $userId): int
    {
        return DB::table('doctors')->insertGetId([
            'user_id' => $userId,
            'name' => 'Test Doctor',
            'slug' => 'test-doctor-' . uniqid(),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function organization(int $ownerUserId, string $name): int
    {
        return DB::table('clinic_organizations')->insertGetId([
            'owner_user_id' => $ownerUserId,
            'name' => $name,
            'slug' => str()->slug($name) . '-' . uniqid(),
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
