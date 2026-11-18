<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('clinic_brain_waitlist_entries', function (Blueprint $table): void {
            $table->foreignId('offered_slot_id')->nullable()->after('notes')->constrained('time_slots')->nullOnDelete();
            $table->dateTime('offered_at')->nullable()->after('offered_slot_id');
            $table->foreignId('fulfilled_appointment_id')->nullable()->after('offered_at')->constrained('appointments')->nullOnDelete();
            $table->index(['offered_slot_id', 'status'], 'waitlist_offer_slot_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clinic_brain_waitlist_entries', function (Blueprint $table): void {
            $table->dropIndex('waitlist_offer_slot_status_idx');
            $table->dropConstrainedForeignId('fulfilled_appointment_id');
            $table->dropConstrainedForeignId('offered_slot_id');
            $table->dropColumn('offered_at');
        });
    }
};
