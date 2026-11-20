<?php

declare(strict_types=1);

namespace Tests\Unit\Portal;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class StaffResponsibilityMigrationTest extends TestCase
{
    public function test_staff_responsibility_migration_creates_and_drops_schema_cleanly(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id();
        });
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
        });

        $migration = require database_path('migrations/2026_11_20_150000_create_clinic_staff_responsibilities_table.php');
        $migration->up();

        $this->assertTrue(Schema::hasTable('clinic_staff_responsibilities'));
        $this->assertTrue(Schema::hasTable('clinic_staff_responsibility_events'));
        $this->assertTrue(Schema::hasColumns('clinic_staff_responsibilities', [
            'organization_id',
            'clinic_id',
            'staff_user_id',
            'title',
            'description',
            'priority',
            'status',
            'effective_from',
            'effective_until',
            'review_at',
            'created_by',
            'updated_by',
            'deleted_at',
        ]));
        $this->assertTrue(Schema::hasColumns('clinic_staff_responsibility_events', [
            'responsibility_id',
            'actor_user_id',
            'event_type',
            'old_values',
            'new_values',
            'reason',
            'created_at',
        ]));

        $migration->down();

        $this->assertFalse(Schema::hasTable('clinic_staff_responsibility_events'));
        $this->assertFalse(Schema::hasTable('clinic_staff_responsibilities'));
    }
}
