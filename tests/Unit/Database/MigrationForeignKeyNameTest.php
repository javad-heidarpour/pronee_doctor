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

    public function test_professional_session_confirmation_foreign_keys_use_explicit_mysql_safe_names(): void
    {
        $migration = $this->migration('2026_11_19_020000_add_professional_dues_and_dual_settlement.php');

        $foreignKeys = [
            'payer_confirmed_by_user_id' => 'cps_payer_confirmed_user_fk',
            'receiver_confirmed_by_user_id' => 'cps_receiver_confirmed_user_fk',
        ];

        foreach ($foreignKeys as $column => $name) {
            $this->assertLessThanOrEqual(64, strlen($name));
            $this->assertStringContainsString(
                "foreign('{$column}', '{$name}')",
                $migration
            );
            $this->assertStringContainsString(
                "dropForeign('{$name}')",
                $migration
            );
        }
    }

    private function migration(string $filename): string
    {
        $content = file_get_contents(base_path('database/migrations/'.$filename));

        $this->assertNotFalse($content);

        return $content;
    }
}
