<?php

declare(strict_types=1);

namespace Tests\Unit\Home;

use App\Actions\Home\BookAppointmentAction;
use App\DTOs\Home\BookAppointmentData;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class BookAppointmentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-17 08:00:00');

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctor_holidays', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('holiday_date');
            $table->string('title', 160);
            $table->text('note')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
            $table->unique(['doctor_id', 'clinic_id', 'holiday_date']);
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone', 45)->nullable();
            $table->string('national_code', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('time_slot_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('patient_national_code')->nullable();
            $table->string('status');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->unsignedBigInteger('visit_number')->nullable();
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->timestamp('reservation_expires_at')->nullable();
            $table->unsignedBigInteger('visit_reason_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('fees', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->unsignedBigInteger('fee_rial');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('visit_reasons', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->unique();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_it_creates_an_appointment_and_books_the_slot(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');

        $visitReasonId = DB::table('visit_reasons')->insertGetId([
            'title' => 'مشاوره عمومی',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('fees')->insert([
            'title' => 'Consultation',
            'fee_rial' => 7000000,
            'is_active' => true,
            'created_by' => $doctor->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment = app(BookAppointmentAction::class)->handle(
            BookAppointmentData::fromValidated([
                'time_slot_id' => $slot->id,
                'patient_name' => 'Test Patient',
                'patient_phone' => '09120000000',
                'patient_national_code' => '0012345678',
                'description' => 'Consultation',
                'visit_reason_id' => $visitReasonId,
            ])
        );

        $this->assertSame('pending', $appointment->status);
        $this->assertSame(7000000, (int) $appointment->fee_rial);
        $this->assertSame('booked', $slot->fresh()->status);
        $this->assertNotNull($appointment->patient_id);
        $this->assertDatabaseHas('patients', [
            'id' => $appointment->patient_id,
            'doctor_id' => $doctor->id,
            'national_code' => '0012345678',
        ]);
        $this->assertNotNull(session('sms.visit.allowed.' . $appointment->id));
        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'time_slot_id' => $slot->id,
            'patient_national_code' => '0012345678',
            'visit_reason_id' => $visitReasonId,
        ]);
    }

    public function test_it_keeps_a_custom_visit_reason_in_the_appointment_without_creating_a_global_reason(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');

        $appointment = app(BookAppointmentAction::class)->handle(
            BookAppointmentData::fromValidated([
                'time_slot_id' => $slot->id,
                'patient_name' => 'Test Patient',
                'patient_phone' => '09120000000',
                'new_visit_reason' => 'پیگیری درمان',
                'description' => 'توضیحات بیمار',
            ])
        );

        $this->assertNull($appointment->visit_reason_id);
        $this->assertStringContainsString('توضیحات بیمار', $appointment->description);
        $this->assertStringContainsString('دلیل مراجعه: پیگیری درمان', $appointment->description);
        $this->assertDatabaseMissing('visit_reasons', ['title' => 'پیگیری درمان']);
    }

    public function test_it_rejects_a_second_active_appointment_for_the_same_patient_and_doctor(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $firstSlot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        $secondSlot = $this->slot($doctor, $clinic, '2026-11-19', '09:00');

        $action = app(BookAppointmentAction::class);
        $data = fn (int $slotId) => BookAppointmentData::fromValidated([
            'time_slot_id' => $slotId,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
        ]);

        $action->handle($data($firstSlot->id));

        $this->expectException(ValidationException::class);
        $action->handle($data($secondSlot->id));
    }

    public function test_it_rejects_a_second_booking_for_the_same_time_slot(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        $action = app(BookAppointmentAction::class);

        $firstAppointment = $action->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $slot->id,
            'patient_name' => 'First Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
        ]));

        try {
            $action->handle(BookAppointmentData::fromValidated([
                'time_slot_id' => $slot->id,
                'patient_name' => 'Second Patient',
                'patient_phone' => '09121111111',
                'patient_national_code' => '0098765432',
            ]));

            $this->fail('A second active appointment for the same time slot must be rejected.');
        } catch (ValidationException) {
            // The original reservation must remain intact.
        }

        $this->assertDatabaseCount('appointments', 1);
        $this->assertDatabaseHas('appointments', [
            'id' => $firstAppointment->id,
            'time_slot_id' => $slot->id,
            'status' => 'pending',
        ]);
        $this->assertSame('booked', $slot->fresh()->status);
    }

    public function test_it_rejects_a_second_active_appointment_for_a_phone_only_patient(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $firstSlot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        $secondSlot = $this->slot($doctor, $clinic, '2026-11-19', '09:00');
        $action = app(BookAppointmentAction::class);

        $action->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $firstSlot->id,
            'patient_name' => 'Phone Only Patient',
            'patient_phone' => '09125555555',
        ]));

        try {
            $action->handle(BookAppointmentData::fromValidated([
                'time_slot_id' => $secondSlot->id,
                'patient_name' => 'Phone Only Patient',
                'patient_phone' => '09125555555',
            ]));

            $this->fail('A second active appointment for the same phone-only patient must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_phone', $exception->errors());
        }

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_it_rejects_booking_on_a_clinic_holiday(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        DoctorHoliday::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'holiday_date' => '2026-11-18',
            'title' => 'تعطیلی',
        ]);

        $this->expectException(ValidationException::class);

        app(BookAppointmentAction::class)->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
        ]));
    }

    public function test_it_rejects_a_slot_from_an_inactive_clinic(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor, active: false);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');

        $this->expectException(ValidationException::class);

        app(BookAppointmentAction::class)->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
        ]));
    }

    public function test_it_rejects_a_slot_with_less_than_one_hour_notice(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-17', '08:30');

        $this->expectException(ValidationException::class);

        app(BookAppointmentAction::class)->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
        ]));
    }

    public function test_it_rejects_a_slot_with_an_active_contacted_appointment(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');

        DB::table('appointments')->insert([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'time_slot_id' => $slot->id,
            'patient_name' => 'Existing Patient',
            'patient_phone' => '09121111111',
            'status' => 'contacted',
            'appointment_date' => $slot->date,
            'appointment_time' => $slot->start_time,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(BookAppointmentAction::class)->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
        ]));
    }

    public function test_it_rejects_a_past_slot(): void
    {
        Carbon::setTestNow('2026-11-17 12:00:00');

        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-17', '09:00');

        $this->expectException(ValidationException::class);

        app(BookAppointmentAction::class)->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $slot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
        ]));
    }

    public function test_it_applies_a_clinic_scoped_discount_and_persists_a_single_redemption(): void
    {
        $this->createDiscountSchema();
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        DB::table('fees')->insert([
            'title' => 'Consultation', 'fee_rial' => 7000000, 'is_active' => true,
            'created_by' => $doctor->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $codeId = DB::table('clinic_discount_codes')->insertGetId([
            'doctor_id' => $doctor->id, 'clinic_id' => $clinic->id, 'created_by_user_id' => 1,
            'code' => 'WELCOME20', 'name' => 'Welcome offer', 'discount_type' => 'percent',
            'discount_value' => 20, 'minimum_amount_rial' => 1000000, 'maximum_discount_rial' => 1000000,
            'usage_limit' => 5, 'per_patient_limit' => 1, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $appointment = app(BookAppointmentAction::class)->handle(BookAppointmentData::fromValidated([
            'time_slot_id' => $slot->id, 'patient_name' => 'Discount Patient',
            'patient_phone' => '09125550000', 'discount_code' => 'welcome20',
        ]));

        $this->assertSame(6000000, (int) $appointment->fee_rial);
        $this->assertSame(7000000, (int) $appointment->original_fee_rial);
        $this->assertSame(1000000, (int) $appointment->discount_amount_rial);
        $this->assertSame($codeId, (int) $appointment->discount_code_id);
        $this->assertDatabaseHas('clinic_discount_redemptions', [
            'appointment_id' => $appointment->id, 'discount_code_id' => $codeId,
            'original_amount_rial' => 7000000, 'discount_amount_rial' => 1000000,
            'final_amount_rial' => 6000000,
        ]);
    }

    public function test_it_rejects_an_expired_or_exhausted_discount_without_booking_the_slot(): void
    {
        $this->createDiscountSchema();
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        DB::table('clinic_discount_codes')->insert([
            'doctor_id' => $doctor->id, 'clinic_id' => $clinic->id, 'created_by_user_id' => 1,
            'code' => 'LIMITED', 'name' => 'Limited offer', 'discount_type' => 'fixed',
            'discount_value' => 500000, 'minimum_amount_rial' => 0, 'maximum_discount_rial' => null,
            'usage_limit' => 1, 'per_patient_limit' => 1, 'status' => 'active',
            'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_discount_redemptions')->insert([
            'discount_code_id' => 1, 'appointment_id' => 999, 'patient_id' => null, 'doctor_id' => $doctor->id,
            'original_amount_rial' => 5000000, 'discount_amount_rial' => 500000,
            'final_amount_rial' => 4500000, 'applied_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);

        try {
            app(BookAppointmentAction::class)->handle(BookAppointmentData::fromValidated([
                'time_slot_id' => $slot->id, 'patient_name' => 'Another Patient',
                'patient_phone' => '09125551111', 'discount_code' => 'LIMITED',
            ]));
            $this->fail('An exhausted discount code must not be applied.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('discount_code', $exception->errors());
        }

        $this->assertSame('available', $slot->fresh()->status);
        $this->assertSame(0, DB::table('appointments')->count());
    }

    private function createDiscountSchema(): void
    {
        Schema::table('appointments', function (Blueprint $table): void {
            $table->unsignedBigInteger('discount_code_id')->nullable();
            $table->unsignedBigInteger('original_fee_rial')->nullable();
            $table->unsignedBigInteger('discount_amount_rial')->default(0);
        });
        Schema::create('clinic_discount_codes', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id'); $table->string('code'); $table->string('name');
            $table->string('discount_type'); $table->unsignedBigInteger('discount_value');
            $table->unsignedBigInteger('minimum_amount_rial')->default(0); $table->unsignedBigInteger('maximum_discount_rial')->nullable();
            $table->unsignedInteger('usage_limit')->nullable(); $table->unsignedSmallInteger('per_patient_limit')->default(1);
            $table->string('status'); $table->timestamp('starts_at')->nullable(); $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
        Schema::create('clinic_discount_redemptions', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('discount_code_id'); $table->unsignedBigInteger('appointment_id')->unique();
            $table->unsignedBigInteger('patient_id')->nullable(); $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('original_amount_rial'); $table->unsignedBigInteger('discount_amount_rial');
            $table->unsignedBigInteger('final_amount_rial'); $table->timestamp('applied_at'); $table->timestamps();
        });
    }

    private function doctor(): Doctor
    {
        return Doctor::query()->create([
            'name' => 'Public booking doctor',
            'slug' => 'public-booking-doctor-' . uniqid(),
            'is_active' => true,
        ]);
    }

    private function clinic(Doctor $doctor, bool $active = true): Address
    {
        return Address::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Test clinic',
            'is_active' => $active,
        ]);
    }

    private function slot(
        Doctor $doctor,
        Address $clinic,
        string $date,
        string $start
    ): TimeSlot {
        return TimeSlot::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinic->id,
            'date' => $date,
            'start_time' => $start,
            'end_time' => Carbon::parse($date . ' ' . $start)->addMinutes(30)->format('H:i:s'),
            'status' => 'available',
        ]);
    }
}
