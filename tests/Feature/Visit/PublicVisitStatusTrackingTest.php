<?php

declare(strict_types=1);

namespace Tests\Feature\Visit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PublicVisitStatusTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createVisitTrackingSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'appointment_payments',
            'appointments',
            'doctor_holidays',
            'time_slots',
            'addresses',
            'doctors',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_signed_visit_page_shows_current_status_without_exposing_patient_phone(): void
    {
        $now = now();

        $userId = DB::table('users')->insertGetId([
            'name' => 'پزشک آزمایشی',
            'email' => 'visit-doctor@example.test',
            'password' => 'not-used',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $doctorId = DB::table('doctors')->insertGetId([
            'user_id' => $userId,
            'name' => 'نمونه',
            'family' => 'پزشکی',
            'slug' => 'sample-public-doctor',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'مطب مرکزی',
            'full_address' => 'نشانی آزمایشی',
            'phone' => '05100000000',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $slotId = DB::table('time_slots')->insertGetId([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'date' => $now->copy()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'booked',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
            'status' => 'pending',
            'appointment_date' => $now->copy()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'fee_rial' => 7000000,
            'reservation_expires_at' => $now->copy()->addMinutes(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $url = URL::temporarySignedRoute(
            'visit.show',
            $now->copy()->addMinutes(15),
            ['visit_number' => $appointmentId]
        );

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Visit/Show')
                ->where('visit.number', $appointmentId)
                ->where('visit.doctor_name', 'دکتر نمونه پزشکی')
                ->where('visit.appointment_status', 'pending')
                ->where('visit.appointment_status_label', 'در انتظار')
                ->where('visit.payment_status', 'unpaid')
                ->where('visit.clinic_name', 'مطب مرکزی')
                ->where('visit.clinic_address', 'نشانی آزمایشی')
                ->where('visit.clinic_phone', '05100000000')
                ->where('visit.patient_name', 'بیمار آزمایشی')
                ->missing('visit.patient_phone'));
    }

    private function createVisitTrackingSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name');
            $table->string('family')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->string('name');
            $table->text('full_address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->foreignId('clinic_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('doctor_holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->foreignId('clinic_id');
            $table->date('holiday_date');
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->foreignId('clinic_id');
            $table->foreignId('time_slot_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('status');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->dateTime('reservation_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id');
            $table->string('status');
            $table->timestamps();
        });
    }
}
