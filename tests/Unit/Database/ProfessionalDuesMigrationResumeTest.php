<?php

declare(strict_types=1);

namespace Tests\Unit\Database;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ProfessionalDuesMigrationResumeTest extends TestCase
{
    public function test_migration_resumes_from_a_partially_applied_session_schema_without_duplicate_columns_or_foreign_keys(): void
    {
        Schema::create('users', fn (Blueprint $table) => $table->id());
        Schema::create('doctors', fn (Blueprint $table) => $table->id());
        Schema::create('addresses', fn (Blueprint $table) => $table->id());
        Schema::create('medical_network_providers', fn (Blueprint $table) => $table->id());

        Schema::create('clinic_professional_partnerships', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_direction');
        });

        // This mirrors the schema-only backup after MySQL applied several
        // ALTER TABLE statements but failed before adding the receiver FK.
        Schema::create('clinic_professional_sessions', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('partnership_id');
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->string('service_name', 160);
            $table->date('service_date');
            $table->unsignedBigInteger('gross_amount_rial');
            $table->unsignedBigInteger('adjustment_amount_rial')->default(0);
            $table->unsignedBigInteger('professional_amount_rial');
            $table->unsignedBigInteger('clinic_amount_rial');
            $table->unsignedBigInteger('settlement_amount_rial')->default(0);
            $table->string('status', 16)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('payer_confirmed_at')->nullable();
            $table->unsignedBigInteger('payer_confirmed_by_user_id')->nullable();
            $table->string('payer_reference', 120)->nullable();
            $table->timestamp('receiver_confirmed_at')->nullable();
            $table->unsignedBigInteger('receiver_confirmed_by_user_id')->nullable();
            $table->unsignedBigInteger('paid_by_user_id')->nullable();
            $table->string('payment_reference', 120)->nullable();
            $table->timestamps();
            $table->foreign(
                'payer_confirmed_by_user_id',
                'clinic_professional_sessions_payer_confirmed_by_user_id_foreign'
            )->references('id')->on('users')->nullOnDelete();
        });

        $partnershipId = DB::table('clinic_professional_partnerships')->insertGetId([
            'payment_direction' => 'professional_to_clinic',
        ]);

        DB::table('clinic_professional_sessions')->insert([
            'partnership_id' => $partnershipId,
            'doctor_id' => 1,
            'clinic_id' => 1,
            'provider_id' => 1,
            'appointment_id' => null,
            'created_by_user_id' => 1,
            'service_name' => 'Test service',
            'service_date' => '2026-11-20',
            'gross_amount_rial' => 1000,
            'adjustment_amount_rial' => 0,
            'professional_amount_rial' => 700,
            'clinic_amount_rial' => 300,
            'settlement_amount_rial' => 0,
            'status' => 'pending',
            'paid_at' => null,
            'payer_confirmed_at' => null,
            'payer_confirmed_by_user_id' => null,
            'payer_reference' => null,
            'receiver_confirmed_at' => null,
            'receiver_confirmed_by_user_id' => null,
            'paid_by_user_id' => null,
            'payment_reference' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require base_path('database/migrations/2026_11_19_020000_add_professional_dues_and_dual_settlement.php');

        $migration->up();

        $this->assertTrue(Schema::hasColumn('clinic_professional_sessions', 'receiver_reference'));
        $this->assertTrue(Schema::hasTable('clinic_professional_dues'));
        $this->assertSame(
            300,
            (int) DB::table('clinic_professional_sessions')->value('settlement_amount_rial')
        );

        $foreignKeys = Schema::getForeignKeys('clinic_professional_sessions');
        $payerKeys = array_filter($foreignKeys, fn (array $key): bool => in_array('payer_confirmed_by_user_id', $key['columns'] ?? [], true));
        $receiverKeys = array_filter($foreignKeys, fn (array $key): bool => in_array('receiver_confirmed_by_user_id', $key['columns'] ?? [], true));

        $this->assertCount(1, $payerKeys);
        $this->assertCount(1, $receiverKeys);

        // A second invocation must be safe while the migration row is still absent.
        $migration->up();

        $foreignKeys = Schema::getForeignKeys('clinic_professional_sessions');
        $payerKeys = array_filter($foreignKeys, fn (array $key): bool => in_array('payer_confirmed_by_user_id', $key['columns'] ?? [], true));
        $receiverKeys = array_filter($foreignKeys, fn (array $key): bool => in_array('receiver_confirmed_by_user_id', $key['columns'] ?? [], true));

        $this->assertCount(1, $payerKeys);
        $this->assertCount(1, $receiverKeys);
        $this->assertTrue(Schema::hasTable('clinic_professional_dues'));
    }
}
