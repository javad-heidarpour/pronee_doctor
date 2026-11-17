<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class CoreSchemaMigrationTest extends TestCase
{
    public function test_fresh_migrations_build_the_core_schema_without_the_separate_blog_tables(): void
    {
        $exitCode = Artisan::call('migrate:fresh', ['--force' => true]);

        $this->assertSame(0, $exitCode, Artisan::output());

        $requiredTables = [
            'users',
            'cities',
            'specialties',
            'doctors',
            'addresses',
            'doctor_user',
            'doctor_working_hours',
            'time_slots',
            'appointments',
            'appointment_payments',
            'fees',
            'visit_reasons',
            'surgery_types',
            'medical_services',
            'surgical_procedures',
            'doctor_images',
            'doctor_socials',
            'contacts',
            'honor_badges',
            'testimonials',
            'reviews',
            'menus',
            'menus_home1',
            'settings',
            'social_platforms',
            'sms_logs',
            'security_audit_logs',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
            'password_reset_tokens',
            'personal_access_tokens',
            'permissions',
            'roles',
            'model_has_permissions',
            'model_has_roles',
            'role_has_permissions',
        ];

        foreach ($requiredTables as $table) {
            $this->assertTrue(
                Schema::hasTable($table),
                "Expected table [{$table}] to exist after a fresh migration."
            );
        }

        $this->assertTrue(Schema::hasColumn('doctor_user', 'clinic_ids'));
        $this->assertTrue(Schema::hasColumn('appointment_payments', 'gateway_name'));
        $this->assertTrue(Schema::hasColumn('appointment_payments', 'ref_num'));

        // Blog is deliberately maintained in a separate project.
        $this->assertFalse(Schema::hasTable('posts'));
        $this->assertFalse(Schema::hasTable('articles'));
        $this->assertFalse(Schema::hasTable('blog_posts'));
    }
}
