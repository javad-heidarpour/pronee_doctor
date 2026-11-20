<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Appointment;

use App\Actions\Portal\Appointment\CreatePortalAppointmentAction;
use App\DTOs\Portal\Appointment\CreatePortalAppointmentData;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class CreatePortalAppointmentActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-17 08:00:00');

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
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
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('patient_national_code')->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->dateTime('reservation_expires_at')->nullable();
            $table->unsignedBigInteger('visit_reason_id')->nullable();
            $table->timestamps();
        });

        Schema::create('fees', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('created_by');
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('fee_rial')->default(0);
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

    public function test_it_creates_appointment_and_marks_the_slot_as_booked(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');

        \Illuminate\Support\Facades\DB::table('fees')->insert([
            'created_by' => $doctor->id,
            'is_active' => true,
            'fee_rial' => 12500000,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $appointment = app(CreatePortalAppointmentAction::class)->handle(
            $doctor,
            CreatePortalAppointmentData::fromValidated([
                'time_slot_id' => $slot->id,
                'patient_name' => 'Test Patient',
                'patient_phone' => '09120000000',
                'patient_national_code' => '0012345678',
                'new_visit_reason' => 'Initial consultation',
            ])
        );

        $this->assertSame('pending', $appointment->status);
        $this->assertSame(12500000, (int) $appointment->fee_rial);
        $this->assertSame('booked', $slot->fresh()->status);
        $this->assertNotNull($appointment->patient_id);
        $this->assertDatabaseHas('patients', [
            'id' => $appointment->patient_id,
            'doctor_id' => $doctor->id,
            'national_code' => '0012345678',
        ]);
        $this->assertDatabaseHas('visit_reasons', [
            'id' => $appointment->visit_reason_id,
            'title' => 'Initial consultation',
        ]);
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

        app(CreatePortalAppointmentAction::class)->handle(
            $doctor,
            $this->appointmentData($slot->id)
        );
    }

    public function test_it_rejects_booking_a_slot_that_is_already_booked(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        $action = app(CreatePortalAppointmentAction::class);
        $data = $this->appointmentData($slot->id);

        $action->handle($doctor, $data);

        $this->expectException(ValidationException::class);
        $action->handle($doctor, $data);
    }

    public function test_it_rejects_a_second_active_appointment_for_the_same_national_code(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $firstSlot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        $secondSlot = $this->slot($doctor, $clinic, '2026-11-19', '09:00');

        $action = app(CreatePortalAppointmentAction::class);
        $action->handle($doctor, $this->appointmentData($firstSlot->id, '0012345678'));

        $this->expectException(ValidationException::class);
        $action->handle($doctor, $this->appointmentData($secondSlot->id, '0012345678'));
    }

    public function test_it_rejects_a_slot_belonging_to_another_doctor(): void
    {
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $otherClinic = $this->clinic($otherDoctor);
        $slot = $this->slot($otherDoctor, $otherClinic, '2026-11-18', '09:00');

        $this->expectException(ValidationException::class);

        app(CreatePortalAppointmentAction::class)->handle(
            $doctor,
            $this->appointmentData($slot->id)
        );
    }

    public function test_it_rejects_a_slot_in_the_past(): void
    {
        Carbon::setTestNow('2026-11-17 12:00:00');

        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $slot = $this->slot($doctor, $clinic, '2026-11-17', '09:00');

        $this->expectException(ValidationException::class);

        app(CreatePortalAppointmentAction::class)->handle(
            $doctor,
            $this->appointmentData($slot->id)
        );
    }

    public function test_it_reuses_the_same_doctor_scoped_patient_for_the_same_national_code(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $firstSlot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        $secondSlot = $this->slot($doctor, $clinic, '2026-11-19', '09:00');

        $action = app(CreatePortalAppointmentAction::class);
        $first = $action->handle($doctor, CreatePortalAppointmentData::fromValidated([
            'time_slot_id' => $firstSlot->id,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
        ]));

        \Illuminate\Support\Facades\DB::table('appointments')
            ->where('id', $first->id)
            ->update(['status' => 'visited']);

        $second = $action->handle($doctor, CreatePortalAppointmentData::fromValidated([
            'time_slot_id' => $secondSlot->id,
            'patient_name' => 'Updated Patient Name',
            'patient_phone' => '09129999999',
            'patient_national_code' => '0012345678',
        ]));

        $this->assertSame($first->patient_id, $second->patient_id);
        $this->assertDatabaseHas('patients', [
            'id' => $first->patient_id,
            'name' => 'Updated Patient Name',
            'phone' => '09129999999',
        ]);
    }

    public function test_it_rejects_a_second_active_appointment_for_a_phone_only_patient(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);
        $firstSlot = $this->slot($doctor, $clinic, '2026-11-18', '09:00');
        $secondSlot = $this->slot($doctor, $clinic, '2026-11-19', '09:00');

        $action = app(CreatePortalAppointmentAction::class);
        $action->handle($doctor, CreatePortalAppointmentData::fromValidated([
            'time_slot_id' => $firstSlot->id,
            'patient_name' => 'Phone Only Patient',
            'patient_phone' => '09125555555',
            'patient_national_code' => null,
        ]));

        try {
            $action->handle($doctor, CreatePortalAppointmentData::fromValidated([
                'time_slot_id' => $secondSlot->id,
                'patient_name' => 'Phone Only Patient',
                'patient_phone' => '09125555555',
                'patient_national_code' => null,
            ]));
            $this->fail('A second active appointment for the same phone-only patient must be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('patient_phone', $exception->errors());
        }
    }

    private function appointmentData(int $slotId, ?string $nationalCode = null): CreatePortalAppointmentData
    {
        return CreatePortalAppointmentData::fromValidated([
            'time_slot_id' => $slotId,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => $nationalCode,
        ]);
    }

    private function doctor(): Doctor
    {
        return Doctor::query()->create([
            'name' => 'Appointment Test Doctor',
            'slug' => 'appointment-test-doctor-' . uniqid(),
        ]);
    }

    private function clinic(Doctor $doctor): Address
    {
        return Address::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Test clinic',
            'is_active' => true,
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
