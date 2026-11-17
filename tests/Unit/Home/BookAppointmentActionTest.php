<?php

declare(strict_types=1);

namespace Tests\Unit\Home;

use App\Actions\Home\BookAppointmentAction;
use App\DTOs\Home\BookAppointmentData;
use App\Models\Address;
use App\Models\Doctor;
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
            $table->timestamps();
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
        ]);
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
