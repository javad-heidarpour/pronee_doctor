<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Tests\TestCase;

final class MigrationForeignKeyNameTest extends TestCase
{
    public function test_treatment_plan_follow_up_consent_foreign_key_uses_a_mysql_safe_name(): void
    {
        $migration = $this->migration('2026_11_18_210000_create_clinic_finance_and_treatment_plans_tables.php');
        $name = 'ptp_followup_sms_consent_user_fk';

        $this->assertLessThanOrEqual(64, strlen($name));
        $this->assertStringContainsString(
            "foreign('follow_up_sms_consent_recorded_by', '{$name}')",
            $migration
        );
    }

    public function test_professional_session_foreign_keys_use_short_names_and_resume_helpers(): void
    {
        $migration = $this->migration('2026_11_19_020000_add_professional_dues_and_dual_settlement.php');

        $foreignKeys = [
            "ensureSessionUserForeignKey('payer_confirmed_by_user_id', 'cps_payer_confirmed_user_fk')",
            "ensureSessionUserForeignKey('receiver_confirmed_by_user_id', 'cps_receiver_confirmed_user_fk')",
        ];

        foreach ($foreignKeys as $definition) {
            $this->assertStringContainsString($definition, $migration);
        }

        $this->assertLessThanOrEqual(64, strlen('cps_payer_confirmed_user_fk'));
        $this->assertLessThanOrEqual(64, strlen('cps_receiver_confirmed_user_fk'));
        $this->assertStringContainsString('addSessionColumnIfMissing', $migration);
        $this->assertStringContainsString('dropSessionForeignKeyForColumn', $migration);
        $this->assertStringNotContainsString(
            'clinic_professional_sessions_receiver_confirmed_by_user_id_foreign',
            $migration
        );
    }

    public function test_long_composite_indexes_use_mysql_safe_names(): void
    {
        $cases = [
            [
                'file' => '2026_11_18_300000_create_clinic_intelligence_modules.php',
                'definition' => "index(['doctor_id', 'scenario_type', 'created_at'], 'ces_doctor_type_created_idx')",
                'name' => 'ces_doctor_type_created_idx',
            ],
            [
                'file' => '2026_11_19_090000_create_clinic_procurement_tables.php',
                'definition' => "index(['purchase_order_id', 'inventory_item_id'], 'cpoi_order_inventory_idx')",
                'name' => 'cpoi_order_inventory_idx',
            ],
        ];

        foreach ($cases as $case) {
            $migration = $this->migration($case['file']);

            $this->assertLessThanOrEqual(64, strlen($case['name']));
            $this->assertStringContainsString($case['definition'], $migration);
        }
    }

    public function test_staff_replacement_foreign_key_uses_a_mysql_safe_name(): void
    {
        $migration = $this->migration('2026_11_19_120000_create_staff_compensation_and_replacement_tables.php');
        $name = 'cssr_replacement_staff_user_fk';

        $this->assertLessThanOrEqual(64, strlen($name));
        $this->assertStringContainsString(
            "constrained('users', '{$name}')",
            $migration
        );
    }

    private function migration(string $filename): string
    {
        $content = file_get_contents(base_path('database/migrations/'.$filename));

        $this->assertNotFalse($content);

        return $content;
    }
}
