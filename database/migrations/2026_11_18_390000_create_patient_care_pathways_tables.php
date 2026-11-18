<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('patient_care_pathways', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('clinic_id')->constrained('addresses')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('patients')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('goal', 500)->nullable();
            $table->string('status', 20)->default('draft');
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->dateTime('activated_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
            $table->index(['doctor_id','clinic_id','patient_id','status'], 'care_pathway_scope_status_idx');
        });
        Schema::create('patient_care_pathway_steps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('pathway_id')->constrained('patient_care_pathways')->cascadeOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->string('category', 40)->default('general');
            $table->dateTime('due_at')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('status', 24)->default('pending');
            $table->dateTime('patient_reported_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('verification_note', 1000)->nullable();
            $table->timestamps();
            $table->index(['pathway_id','status','due_at'], 'care_pathway_step_status_due_idx');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('patient_care_pathway_steps');
        Schema::dropIfExists('patient_care_pathways');
    }
};
