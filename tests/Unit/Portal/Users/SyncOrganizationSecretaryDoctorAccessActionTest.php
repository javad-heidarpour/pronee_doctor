<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Users;

use App\Actions\Portal\Users\SyncOrganizationSecretaryDoctorAccessAction;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class SyncOrganizationSecretaryDoctorAccessActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('role')->default('secretary');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('status')->default('pending');
            $table->primary(['organization_id', 'doctor_id']);
        });

        Schema::create('clinic_organization_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->text('permissions')->nullable();
            $table->text('clinic_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->primary(['organization_id', 'user_id']);
        });

        Schema::create('clinic_organization_clinics', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('status')->default('active');
            $table->primary(['organization_id', 'clinic_id']);
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
            $table->primary(['doctor_id', 'user_id']);
        });
    }

    public function test_access_is_merged_from_all_active_organizations_without_losing_direct_grants(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $firstClinic = $this->clinic($doctor, 101);
        $secondClinic = $this->clinic($doctor, 202);

        $this->organizationGrant(101, $doctor, $secretary, $firstClinic, ['appointments.view']);
        $this->organizationGrant(202, $doctor, $secretary, $secondClinic, ['patients.view']);

        DB::table('doctor_user')->insert([
            'doctor_id' => $doctor->id,
            'user_id' => $secretary->id,
            'permissions' => json_encode(['finance.view']),
            'clinic_ids' => json_encode([$firstClinic->id]),
            'managed_by_organization' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

        $pivot = DB::table('doctor_user')->where('doctor_id', $doctor->id)->where('user_id', $secretary->id)->first();

        $this->assertSameCanonical(['finance.view', 'appointments.view', 'patients.view'], json_decode($pivot->permissions, true));
        $this->assertSameCanonical([$firstClinic->id, $secondClinic->id], json_decode($pivot->clinic_ids, true));
        $this->assertTrue((bool) $pivot->managed_by_organization);
        $this->assertSame(['finance.view'], json_decode($pivot->base_permissions, true));
        $this->assertSame([$firstClinic->id], json_decode($pivot->base_clinic_ids, true));
    }

    public function test_disabling_one_membership_keeps_other_organization_grants(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $firstClinic = $this->clinic($doctor, 101);
        $secondClinic = $this->clinic($doctor, 202);

        $this->organizationGrant(101, $doctor, $secretary, $firstClinic, ['appointments.view']);
        $this->organizationGrant(202, $doctor, $secretary, $secondClinic, ['patients.view']);

        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

        DB::table('clinic_organization_members')
            ->where('organization_id', 101)
            ->where('user_id', $secretary->id)
            ->update(['is_active' => false]);

        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

        $pivot = DB::table('doctor_user')->where('doctor_id', $doctor->id)->where('user_id', $secretary->id)->first();

        $this->assertSame(['patients.view'], json_decode($pivot->permissions, true));
        $this->assertSame([$secondClinic->id], json_decode($pivot->clinic_ids, true));
    }

    public function test_organization_grants_cannot_exceed_two_clinics_across_the_secretary_account(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $firstClinic = $this->clinic($doctor, 601);
        $secondClinic = $this->clinic($doctor, 602);
        $thirdClinic = $this->clinic($doctor, 603);

        $this->organizationGrant(601, $doctor, $secretary, $firstClinic, ['appointments.view']);
        $this->organizationGrant(602, $doctor, $secretary, $secondClinic, ['patients.view']);
        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

        $this->organizationGrant(603, $doctor, $secretary, $thirdClinic, ['finance.view']);

        try {
            app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);
            $this->fail('The third active clinic grant must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('clinic_ids', $exception->errors());
        }

        $pivot = DB::table('doctor_user')
            ->where('doctor_id', $doctor->id)
            ->where('user_id', $secretary->id)
            ->first();

        $this->assertSameCanonical(
            [$firstClinic->id, $secondClinic->id],
            json_decode($pivot->clinic_ids, true)
        );
    }

    public function test_pending_doctor_membership_and_inactive_user_do_not_grant_access(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor, 303);

        $this->organizationGrant(303, $doctor, $secretary, $clinic, ['appointments.manage'], doctorStatus: 'pending');

        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);
        $this->assertDatabaseMissing('doctor_user', [
            'doctor_id' => $doctor->id,
            'user_id' => $secretary->id,
        ]);

        DB::table('clinic_organization_doctors')
            ->where('organization_id', 303)
            ->where('doctor_id', $doctor->id)
            ->update(['status' => 'active']);
        $secretary->forceFill(['is_active' => false])->save();

        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

        $this->assertDatabaseMissing('doctor_user', [
            'doctor_id' => $doctor->id,
            'user_id' => $secretary->id,
        ]);
    }

    public function test_inactive_doctor_profile_or_owner_account_does_not_grant_access(): void
    {
        foreach (['profile', 'owner'] as $inactiveTarget) {
            $secretary = $this->secretary();
            $doctor = $this->doctor();
            $clinic = $this->clinic($doctor, 505);

            $this->organizationGrant(505, $doctor, $secretary, $clinic, ['appointments.view']);

            if ($inactiveTarget === 'profile') {
                $doctor->forceFill(['is_active' => false])->save();
            } else {
                $doctor->user()->firstOrFail()->forceFill(['is_active' => false])->save();
            }

            app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

            $this->assertDatabaseMissing('doctor_user', [
                'doctor_id' => $doctor->id,
                'user_id' => $secretary->id,
            ]);
        }
    }

    public function test_inactive_or_detached_clinics_do_not_grant_organization_permissions(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor, 404);

        $this->organizationGrant(404, $doctor, $secretary, $clinic, ['appointments.view']);
        DB::table('clinic_organization_clinics')
            ->where('organization_id', 404)
            ->where('clinic_id', $clinic->id)
            ->update(['status' => 'inactive']);

        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

        $this->assertDatabaseMissing('doctor_user', [
            'doctor_id' => $doctor->id,
            'user_id' => $secretary->id,
        ]);
    }

    public function test_revoking_last_organization_grant_restores_direct_assignment(): void
    {
        $secretary = $this->secretary();
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor, null);

        DB::table('doctor_user')->insert([
            'doctor_id' => $doctor->id,
            'user_id' => $secretary->id,
            'permissions' => json_encode(['finance.view']),
            'clinic_ids' => json_encode([$clinic->id]),
            'base_permissions' => json_encode(['finance.view']),
            'base_clinic_ids' => json_encode([$clinic->id]),
            'managed_by_organization' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($secretary, $doctor);

        $pivot = DB::table('doctor_user')->where('doctor_id', $doctor->id)->where('user_id', $secretary->id)->first();

        $this->assertSame(['finance.view'], json_decode($pivot->permissions, true));
        $this->assertSame([$clinic->id], json_decode($pivot->clinic_ids, true));
        $this->assertFalse((bool) $pivot->managed_by_organization);
        $this->assertNull($pivot->base_permissions);
        $this->assertNull($pivot->base_clinic_ids);
    }

    private function secretary(): User
    {
        return User::query()->create([
            'name' => 'Test secretary',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'secretary',
            'is_active' => true,
        ]);
    }

    private function doctor(): Doctor
    {
        $owner = User::query()->create([
            'name' => 'Doctor owner',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'doctor',
            'is_active' => true,
        ]);

        return Doctor::query()->create([
            'user_id' => $owner->id,
            'name' => 'Test doctor',
            'slug' => 'test-doctor-' . uniqid(),
            'is_active' => true,
        ]);
    }

    private function clinic(Doctor $doctor, ?int $organizationId): object
    {
        $id = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'organization_id' => $organizationId,
            'name' => 'Test clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (object) ['id' => $id];
    }

    private function organizationGrant(
        int $organizationId,
        Doctor $doctor,
        User $secretary,
        object $clinic,
        array $permissions,
        string $doctorStatus = 'active'
    ): void {
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $organizationId,
            'doctor_id' => $doctor->id,
            'status' => $doctorStatus,
        ]);
        DB::table('clinic_organization_members')->insert([
            'organization_id' => $organizationId,
            'user_id' => $secretary->id,
            'permissions' => json_encode($permissions),
            'clinic_ids' => json_encode([$clinic->id]),
            'is_active' => true,
        ]);
        DB::table('clinic_organization_clinics')->insert([
            'organization_id' => $organizationId,
            'clinic_id' => $clinic->id,
            'status' => 'active',
        ]);
    }

    private function assertSameCanonical(array $expected, array $actual): void
    {
        sort($expected);
        sort($actual);
        $this->assertSame($expected, $actual);
    }
}
