<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Users;

use App\Actions\Portal\Users\UpdateManagedUserAction;
use App\Actions\Portal\Users\RestoreOrDeleteManagedUserAction;
use App\Actions\Portal\Users\ChangeManagedUserStatusAction;
use App\Actions\Portal\Users\CreateManagedUserAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class UpdateManagedUserActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['session.driver' => 'array']);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('email')->nullable();
            $table->string('username')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('password')->nullable();
            $table->string('remember_token')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
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

        Schema::create('clinic_organization_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach (['clinic_organization_members', 'doctor_user', 'doctors', 'users'] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_deleting_secretary_revokes_organization_and_direct_grants_before_soft_delete(): void
    {
        DB::table('users')->insert([
            'id' => 11,
            'name' => 'Deleted Secretary',
            'role' => 'secretary',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctors')->insert([
            'id' => 21,
            'user_id' => 31,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_user')->insert([
            'doctor_id' => 21,
            'user_id' => 11,
            'permissions' => json_encode(['appointments.view']),
            'clinic_ids' => json_encode([102]),
            'managed_by_organization' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('clinic_organization_members')->insert([
            'organization_id' => 41,
            'user_id' => 11,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(11);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        $restored = app(\App\Actions\Portal\Users\RestoreOrDeleteManagedUserAction::class)
            ->handle($user, $audit);

        $this->assertFalse($restored);
        $this->assertTrue($user->fresh()->trashed());
        $this->assertFalse((bool) $user->fresh()->is_active);
        $this->assertDatabaseHas('clinic_organization_members', [
            'organization_id' => 41,
            'user_id' => 11,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('doctor_user', [
            'doctor_id' => 21,
            'user_id' => 11,
        ]);
    }

    public function test_deleting_and_restoring_a_doctor_account_keeps_its_public_profile_inactive(): void
    {
        DB::table('users')->insert([
            'id' => 12,
            'name' => 'Doctor Account',
            'role' => 'doctor',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctors')->insert([
            'id' => 22,
            'user_id' => 12,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->twice();

        $user = User::query()->findOrFail(12);
        $deleted = app(RestoreOrDeleteManagedUserAction::class)->handle($user, $audit);

        $this->assertFalse($deleted);
        $this->assertTrue($user->fresh()->trashed());
        $this->assertFalse((bool) DB::table('doctors')->where('id', 22)->value('is_active'));

        $trashedUser = User::withTrashed()->findOrFail(12);
        $restored = app(RestoreOrDeleteManagedUserAction::class)->handle($trashedUser, $audit);

        $this->assertTrue($restored);
        $this->assertFalse($trashedUser->fresh()->trashed());
        $this->assertFalse((bool) $trashedUser->fresh()->is_active);
        $this->assertFalse((bool) DB::table('doctors')->where('id', 22)->value('is_active'));
    }

    public function test_creating_a_secretary_for_a_doctor_creates_the_direct_assignment(): void
    {
        DB::table('doctors')->insert([
            'id' => 23,
            'user_id' => 33,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $doctor = Doctor::query()->findOrFail(23);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        $user = app(CreateManagedUserAction::class)->handle([
            'name' => 'New',
            'family' => 'Secretary',
            'phone' => '09120000001',
            'email' => null,
            'username' => 'new.secretary',
            'password' => 'Password123!',
            'role' => 'secretary',
            'is_active' => true,
        ], $audit, $doctor);

        $this->assertSame('secretary', $user->role);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('doctor_user', [
            'doctor_id' => 23,
            'user_id' => $user->id,
        ]);
    }

    public function test_deactivating_a_doctor_account_also_deactivates_its_profile(): void
    {
        DB::table('users')->insert([
            'id' => 14,
            'name' => 'Doctor To Disable',
            'role' => 'doctor',
            'is_active' => true,
            'remember_token' => 'old-remember-token',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctors')->insert([
            'id' => 24,
            'user_id' => 14,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(14);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        $updated = app(ChangeManagedUserStatusAction::class)->handle($user, false, $audit);

        $this->assertFalse((bool) $updated->is_active);
        $this->assertNotSame('old-remember-token', $updated->remember_token);
        $this->assertFalse((bool) DB::table('doctors')->where('id', 24)->value('is_active'));
    }

    public function test_admin_cannot_assign_doctor_role_without_a_doctor_profile(): void
    {
        DB::table('users')->insert([
            'id' => 13,
            'name' => 'Account Without Profile',
            'role' => 'accountant',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(13);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        try {
            app(UpdateManagedUserAction::class)->handle(
                $user,
                ['role' => 'doctor'],
                $audit
            );
            $this->fail('A doctor role requires a doctor profile.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('role', $exception->errors());
        }

        $this->assertSame('accountant', $user->fresh()->role);
    }

    public function test_changing_secretary_role_deactivates_organization_memberships_and_removes_direct_grants(): void
    {
        DB::table('users')->insert([
            'id' => 10,
            'name' => 'Test Secretary',
            'role' => 'secretary',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctors')->insert([
            'id' => 20,
            'user_id' => 30,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('doctor_user')->insert([
            'doctor_id' => 20,
            'user_id' => 10,
            'permissions' => json_encode(['appointments.view']),
            'clinic_ids' => json_encode([101]),
            'managed_by_organization' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('clinic_organization_members')->insert([
            'organization_id' => 40,
            'user_id' => 10,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = User::query()->findOrFail(10);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        app(UpdateManagedUserAction::class)->handle(
            $user,
            ['role' => 'accountant'],
            $audit
        );

        $this->assertSame('accountant', $user->fresh()->role);
        $this->assertDatabaseHas('clinic_organization_members', [
            'organization_id' => 40,
            'user_id' => 10,
            'is_active' => false,
        ]);
        $this->assertDatabaseMissing('doctor_user', [
            'doctor_id' => 20,
            'user_id' => 10,
        ]);
    }
}
