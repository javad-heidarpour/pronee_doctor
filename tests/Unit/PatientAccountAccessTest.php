<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\PatientAccountAccess;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class PatientAccountAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_owner_has_full_access_even_when_permissions_are_empty(): void
    {
        $access = $this->createAccess('owner', []);

        $this->assertTrue($access->allows('view_summary'));
        $this->assertTrue($access->allows('view_records'));
        $this->assertTrue($access->allows('manage_caregivers'));
        $this->assertTrue($access->allows('any_future_owner_permission'));
    }

    public function test_caregiver_can_only_use_explicitly_granted_permissions(): void
    {
        $access = $this->createAccess('caregiver', ['view_summary', 'view_appointments']);

        $this->assertTrue($access->allows('view_summary'));
        $this->assertTrue($access->allows('view_appointments'));
        $this->assertFalse($access->allows('view_records'));
        $this->assertFalse($access->allows('manage_caregivers'));
        $this->assertFalse($access->allows('manage_medication'));
    }

    public function test_unknown_access_type_never_receives_permissions(): void
    {
        $access = $this->createAccess('unknown', ['view_summary', 'view_records']);

        $this->assertFalse($access->allows('view_summary'));
        $this->assertFalse($access->allows('view_records'));
    }

    public function test_active_scope_excludes_revoked_and_expired_access(): void
    {
        $activeId = $this->insertAccess('caregiver', ['view_summary'], null, null);
        $revokedId = $this->insertAccess('caregiver', ['view_summary'], null, now());
        $expiredId = $this->insertAccess('caregiver', ['view_summary'], now()->subMinute(), null);
        $futureId = $this->insertAccess('caregiver', ['view_summary'], now()->addDay(), null);

        $ids = PatientAccountAccess::query()->active()->orderBy('id')->pluck('id')->all();

        $this->assertSame([$activeId, $futureId], $ids);
        $this->assertNotContains($revokedId, $ids);
        $this->assertNotContains($expiredId, $ids);
    }

    private function createAccess(string $type, array $permissions): PatientAccountAccess
    {
        $id = $this->insertAccess($type, $permissions, null, null);

        return PatientAccountAccess::query()->findOrFail($id);
    }

    private function insertAccess(string $type, array $permissions, $expiresAt, $revokedAt): int
    {
        return (int) DB::table('patient_account_accesses')->insertGetId([
            'patient_id' => 1,
            'user_id' => 10 + (int) DB::table('patient_account_accesses')->count(),
            'access_type' => $type,
            'permissions' => json_encode($permissions),
            'expires_at' => $expiresAt,
            'revoked_at' => $revokedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
