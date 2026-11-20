<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('clinic_business_leads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id', 'cbl_doctor_fk')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id', 'cbl_clinic_fk')->nullable()->constrained('addresses')->nullOnDelete();
            $table->foreignId('campaign_id', 'cbl_campaign_fk')->nullable()->constrained('clinic_marketing_campaigns')->nullOnDelete();
            $table->foreignId('created_by_user_id', 'cbl_creator_fk')->constrained('users')->restrictOnDelete();
            $table->foreignId('converted_appointment_id', 'cbl_appointment_fk')->nullable()->constrained('appointments')->nullOnDelete();
            $table->string('name', 160);
            $table->longText('phone');
            $table->longText('email')->nullable();
            $table->string('source', 32)->default('other');
            $table->string('status', 20)->default('new');
            $table->boolean('contact_consent')->default(false);
            $table->timestamp('consent_at')->nullable();
            $table->timestamp('next_follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();
            $table->index(['doctor_id', 'status', 'next_follow_up_at'], 'cbl_doctor_status_followup_idx');
            $table->index(['clinic_id', 'created_at'], 'cbl_clinic_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clinic_business_leads');
    }
};
