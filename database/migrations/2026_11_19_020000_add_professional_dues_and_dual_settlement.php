<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const SESSION_TABLE = 'clinic_professional_sessions';

    public function up(): void
    {
        // MySQL may commit earlier ALTER TABLE statements before a later
        // foreign-key statement fails. Resume from the actual schema instead
        // of attempting to add already-created columns again.
        $this->addSessionColumnIfMissing('settlement_amount_rial', function (Blueprint $table): void {
            $table->unsignedBigInteger('settlement_amount_rial')->default(0)->after('clinic_amount_rial');
        });
        $this->addSessionColumnIfMissing('payer_confirmed_at', function (Blueprint $table): void {
            $table->timestamp('payer_confirmed_at')->nullable()->after('paid_at');
        });
        $this->addSessionColumnIfMissing('payer_confirmed_by_user_id', function (Blueprint $table): void {
            $table->unsignedBigInteger('payer_confirmed_by_user_id')->nullable()->after('payer_confirmed_at');
        });
        $this->addSessionColumnIfMissing('payer_reference', function (Blueprint $table): void {
            $table->string('payer_reference', 120)->nullable()->after('payer_confirmed_by_user_id');
        });
        $this->addSessionColumnIfMissing('receiver_confirmed_at', function (Blueprint $table): void {
            $table->timestamp('receiver_confirmed_at')->nullable()->after('payer_reference');
        });
        $this->addSessionColumnIfMissing('receiver_confirmed_by_user_id', function (Blueprint $table): void {
            $table->unsignedBigInteger('receiver_confirmed_by_user_id')->nullable()->after('receiver_confirmed_at');
        });
        $this->addSessionColumnIfMissing('receiver_reference', function (Blueprint $table): void {
            $table->string('receiver_reference', 120)->nullable()->after('receiver_confirmed_by_user_id');
        });

        // Keep identifiers comfortably below MySQL's 64-character limit.
        // Existing valid constraints from a partially applied migration are retained.
        $this->ensureSessionUserForeignKey('payer_confirmed_by_user_id', 'cps_payer_confirmed_user_fk');
        $this->ensureSessionUserForeignKey('receiver_confirmed_by_user_id', 'cps_receiver_confirmed_user_fk');

        if (Schema::hasColumn(self::SESSION_TABLE, 'status')) {
            Schema::table(self::SESSION_TABLE, function (Blueprint $table): void {
                $table->string('status', 24)->default('pending')->change();
            });
        }

        DB::table(self::SESSION_TABLE)->orderBy('id')->chunkById(500, function ($sessions): void {
            foreach ($sessions as $session) {
                $direction = DB::table('clinic_professional_partnerships')
                    ->where('id', $session->partnership_id)
                    ->value('payment_direction');

                $amount = $direction === 'professional_to_clinic'
                    ? (int) $session->clinic_amount_rial
                    : (int) $session->professional_amount_rial;

                DB::table(self::SESSION_TABLE)
                    ->where('id', $session->id)
                    ->update(['settlement_amount_rial' => $amount]);
            }
        });

        if (!Schema::hasTable('clinic_professional_dues')) {
            Schema::create('clinic_professional_dues', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('partnership_id')->constrained('clinic_professional_partnerships')->restrictOnDelete();
                $table->foreignId('doctor_id')->constrained('doctors')->restrictOnDelete();
                $table->foreignId('clinic_id')->constrained('addresses')->restrictOnDelete();
                $table->foreignId('provider_id')->constrained('medical_network_providers')->restrictOnDelete();
                $table->foreignId('clinic_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('professional_user_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('created_by_user_id')->constrained('users')->restrictOnDelete();
                $table->date('period_start');
                $table->date('period_end');
                $table->date('due_date');
                $table->unsignedBigInteger('amount_rial');
                $table->string('payment_direction', 24);
                $table->string('status', 24)->default('pending');
                $table->timestamp('payer_confirmed_at')->nullable();
                $table->foreignId('payer_confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('payer_reference', 120)->nullable();
                $table->timestamp('receiver_confirmed_at')->nullable();
                $table->foreignId('receiver_confirmed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('receiver_reference', 120)->nullable();
                $table->timestamps();
                $table->unique(['partnership_id', 'period_start', 'period_end'], 'cpd_partnership_period_unique');
                $table->index(['doctor_id', 'clinic_id', 'status'], 'cpd_clinic_status');
                $table->index(['professional_user_id', 'status'], 'cpd_professional_status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_professional_dues');

        if (!Schema::hasTable(self::SESSION_TABLE)) {
            return;
        }

        if (Schema::hasColumn(self::SESSION_TABLE, 'status')) {
            DB::table(self::SESSION_TABLE)
                ->where('status', 'partially_confirmed')
                ->update(['status' => 'pending']);
        }

        $this->dropSessionForeignKeyForColumn('payer_confirmed_by_user_id');
        $this->dropSessionForeignKeyForColumn('receiver_confirmed_by_user_id');

        if (Schema::hasColumn(self::SESSION_TABLE, 'status')) {
            Schema::table(self::SESSION_TABLE, function (Blueprint $table): void {
                $table->string('status', 16)->default('pending')->change();
            });
        }

        $columns = [
            'settlement_amount_rial',
            'payer_confirmed_at',
            'payer_confirmed_by_user_id',
            'payer_reference',
            'receiver_confirmed_at',
            'receiver_confirmed_by_user_id',
            'receiver_reference',
        ];

        $existingColumns = array_values(array_filter(
            $columns,
            fn (string $column): bool => Schema::hasColumn(self::SESSION_TABLE, $column)
        ));

        if ($existingColumns !== []) {
            Schema::table(self::SESSION_TABLE, function (Blueprint $table) use ($existingColumns): void {
                $table->dropColumn($existingColumns);
            });
        }
    }

    private function addSessionColumnIfMissing(string $column, \Closure $definition): void
    {
        if (Schema::hasColumn(self::SESSION_TABLE, $column)) {
            return;
        }

        Schema::table(self::SESSION_TABLE, function (Blueprint $table) use ($definition): void {
            $definition($table);
        });
    }

    private function ensureSessionUserForeignKey(string $column, string $name): void
    {
        $existing = collect(Schema::getForeignKeys(self::SESSION_TABLE))
            ->first(fn (array $foreignKey): bool => in_array($column, $foreignKey['columns'] ?? [], true));

        if ($existing !== null) {
            $foreignTable = $existing['foreign_table'] ?? null;

            if ($foreignTable !== null && $foreignTable !== 'users') {
                throw new \RuntimeException("Foreign key for [{$column}] must reference [users].");
            }

            return;
        }

        Schema::table(self::SESSION_TABLE, function (Blueprint $table) use ($column, $name): void {
            $table->foreign($column, $name)->references('id')->on('users')->nullOnDelete();
        });
    }

    private function dropSessionForeignKeyForColumn(string $column): void
    {
        $existing = collect(Schema::getForeignKeys(self::SESSION_TABLE))
            ->first(fn (array $foreignKey): bool => in_array($column, $foreignKey['columns'] ?? [], true));

        if ($existing === null) {
            return;
        }

        $name = $existing['name'] ?? null;

        Schema::table(self::SESSION_TABLE, function (Blueprint $table) use ($name, $column): void {
            $table->dropForeign($name ?: [$column]);
        });
    }
};
