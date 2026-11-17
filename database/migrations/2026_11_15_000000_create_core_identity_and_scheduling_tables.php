<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('family', 100);
            $table->string('email')->nullable()->unique();
            $table->string('username', 100)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('phone')->nullable()->unique();
            $table->enum('role', ['admin', 'doctor', 'secretary', 'user'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('province')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('specialties', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable()->unique();
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('visit_reasons', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->unique();
            $table->timestamps();
        });

        Schema::create('surgery_types', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->unique();
            $table->timestamps();
        });

        Schema::create('social_platforms', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 50);
            $table->string('title', 100);
            $table->string('icon');
            $table->string('color', 20)->nullable();
            $table->string('base_url')->nullable();
            $table->integer('sort_order')->nullable()->default(0);
            $table->boolean('is_active')->nullable()->default(true);
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->foreignId('specialty_id')->nullable()->constrained('specialties')->nullOnDelete();
            $table->string('caption')->nullable();
            $table->string('name');
            $table->string('family');
            $table->string('slug')->unique();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->string('clinic_address', 200)->nullable();
            $table->string('photo')->nullable();
            $table->bigInteger('Medical_system_number')->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('specialty_title')->nullable();
            $table->integer('years_of_experience')->default(0);
            $table->decimal('satisfaction_rate', 4, 2)->default(98.00);
            $table->boolean('is_active')->default(true);
            $table->enum('registration_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->dateTime('site_expires_at')->nullable();
            $table->dateTime('trial_started_at')->nullable();
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('role')->default('doctor');
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->nullable()->constrained('doctors')->cascadeOnDelete();
            $table->string('name');
            $table->string('full_address')->nullable();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();
            $table->string('phone', 45)->nullable();
            $table->string('Longitude')->nullable();
            $table->string('Latitude')->nullable();
            $table->integer('Altitude')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['id', 'doctor_id'], 'addresses_id_doctor_id_unique');
        });

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->json('permissions')->nullable();
            $table->timestamps();
            $table->unique(['doctor_id', 'user_id'], 'doctor_user_unique');
            $table->index('user_id', 'doctor_user_user_fk');
        });

        Schema::create('doctor_working_hours', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedBigInteger('clinic_id');
            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('duration_minutes')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('clinic_id', 'doctor_working_hours_clinic_id_foreign');
            $table->index(
                ['doctor_id', 'clinic_id', 'day_of_week'],
                'doctor_working_hours_doctor_id_clinic_id_day_of_week_index'
            );
            $table->index(['clinic_id', 'doctor_id'], 'working_hours_clinic_doctor_fk');
            $table->foreign(['clinic_id', 'doctor_id'], 'working_hours_clinic_doctor_fk_constraint')
                ->references(['id', 'doctor_id'])
                ->on('addresses')
                ->cascadeOnDelete();
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedBigInteger('clinic_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['available', 'booked', 'cancelled'])->default('available');
            $table->timestamps();
            $table->unique(['id', 'clinic_id', 'doctor_id'], 'time_slots_id_clinic_doctor_unique');
            $table->index('clinic_id', 'time_slots_clinic_id_foreign');
            $table->index(
                ['doctor_id', 'clinic_id', 'date', 'status'],
                'time_slots_doctor_id_clinic_id_date_status_index'
            );
            $table->index(['clinic_id', 'doctor_id'], 'time_slots_clinic_doctor_fk_index');
            $table->foreign(['clinic_id', 'doctor_id'], 'time_slots_clinic_doctor_fk')
                ->references(['id', 'doctor_id'])
                ->on('addresses')
                ->cascadeOnDelete();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visit_reason_id')->nullable()->constrained('visit_reasons')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('surgery_type_id')->nullable()->constrained('surgery_types')->nullOnDelete()->cascadeOnUpdate();
            $table->date('surgery_date')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('time_slot_id');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('patient_national_code')->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'visited'])->default('pending');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->timestamps();
            $table->unsignedBigInteger('visit_number')->nullable()->unique('appointments_visit_number_unique');
            $table->unsignedBigInteger('fee_rial')->default(5000);
            $table->timestamp('reservation_expires_at')->nullable();
            $table->index('clinic_id', 'appointments_clinic_id_foreign');
            $table->index('user_id', 'appointments_user_id_foreign');
            $table->index(['doctor_id', 'appointment_date'], 'appointments_doctor_id_appointment_date_index');
            $table->index(['time_slot_id', 'status'], 'appointments_time_slot_id_status_index');
            $table->index(['clinic_id', 'doctor_id'], 'appointments_clinic_doctor_index');
            $table->index(
                ['time_slot_id', 'clinic_id', 'doctor_id'],
                'appointments_slot_clinic_doctor_index'
            );
            $table->foreign(['clinic_id', 'doctor_id'], 'appointments_clinic_doctor_fk')
                ->references(['id', 'doctor_id'])
                ->on('addresses')
                ->cascadeOnDelete();
            $table->foreign(['time_slot_id', 'clinic_id', 'doctor_id'], 'appointments_slot_clinic_doctor_fk')
                ->references(['id', 'clinic_id', 'doctor_id'])
                ->on('time_slots')
                ->cascadeOnDelete();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->unsignedBigInteger('amount_rial');
            $table->string('status', 20)->default('pending');
            $table->uuid('payment_uuid')->unique();
            $table->string('gateway_token')->nullable();
            $table->string('ref_num')->nullable();
            $table->string('terminal_id')->nullable();
            $table->string('gateway_name', 50)->default('sep');
            $table->text('gateway_result_message')->nullable();
            $table->string('gateway_result_code', 50)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('gateway_verified_at')->nullable();
            $table->timestamps();
            $table->index(['appointment_id', 'status'], 'appointment_payments_appointment_id_status_index');
            $table->index('gateway_token', 'appointment_payments_gateway_token_index');
            $table->index('ref_num', 'appointment_payments_gateway_reference_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_payments');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('time_slots');
        Schema::dropIfExists('doctor_working_hours');
        Schema::dropIfExists('doctor_user');
        Schema::dropIfExists('addresses');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('social_platforms');
        Schema::dropIfExists('surgery_types');
        Schema::dropIfExists('visit_reasons');
        Schema::dropIfExists('specialties');
        Schema::dropIfExists('cities');
        Schema::dropIfExists('users');
    }
};
