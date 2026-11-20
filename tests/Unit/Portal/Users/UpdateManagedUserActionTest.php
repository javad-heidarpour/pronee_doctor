<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Users;

use App\Actions\Portal\Users\UpdateManagedUserAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
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
