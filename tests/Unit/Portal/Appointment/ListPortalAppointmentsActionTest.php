<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Appointment;

use App\Actions\Portal\Appointment\ListPortalAppointmentsAction;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ListPortalAppointmentsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->unsignedBigInteger('city_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('visit_reasons', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('surgery_types', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->string('status')->nullable();
            $table->string('gateway_name')->nullable();
            $table->string('gateway_token')->nullable();
            $table->string('ref_num')->nullable();
            $table->string('terminal_id')->nullable();
            $table->text('gateway_result_message')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('time_slot_id')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_phone')->nullable();
            $table->string('patient_national_code')->nullable();
            $table->string('status')->default('pending');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->unsignedBigInteger('visit_reason_id')->nullable();
            $table->unsignedBigInteger('surgery_type_id')->nullable();
            $table->date('surgery_date')->nullable();
            $table->text('description')->nullable();
            $table->string('visit_number')->nullable();
            $table->unsignedBigInteger('fee_rial')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_returns_only_fields_used_by_the_appointment_list(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Appointment List Test Doctor',
            'slug' => 'appointment-list-test-doctor',
        ]);

        DB::table('addresses')->insert([
            'id' => 10,
            'doctor_id' => $doctor->id,
            'name' => 'Test Clinic',
        ]);
        DB::table('visit_reasons')->insert([
            'id' => 1,
            'title' => 'Consultation',
        ]);
        DB::table('surgery_types')->insert([
            'id' => 1,
            'title' => 'Procedure',
        ]);

        $appointment = Appointment::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => 10,
            'user_id' => 99,
            'time_slot_id' => 88,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
            'status' => 'visited',
            'appointment_date' => '2026-11-20',
            'appointment_time' => '09:30',
            'visit_reason_id' => 1,
            'surgery_type_id' => 1,
            'surgery_date' => '2026-11-21',
            'description' => 'Private visit note',
            'visit_number' => 'VISIT-001',
            'fee_rial' => 1250000,
        ]);

        DB::table('appointment_payments')->insert([
            'appointment_id' => $appointment->id,
            'status' => 'paid',
            'gateway_name' => 'sep',
            'gateway_token' => 'private-token',
            'ref_num' => 'private-reference',
            'terminal_id' => 'private-terminal',
            'gateway_result_message' => 'private gateway response',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = app(ListPortalAppointmentsAction::class)->handle($doctor, [10], []);
        $loaded = $result['appointments']->firstOrFail();

        $this->assertSame('paid', $loaded->payment_status);
        $this->assertSame('Test Clinic', $loaded->clinic->name);
        $this->assertSame('Consultation', $loaded->visitReason->title);
        $this->assertSame('Procedure', $loaded->surgeryType->title);
        $this->assertSame('VISIT-001', $loaded->visit_number);
        $this->assertFalse($loaded->relationLoaded('user'));
        $this->assertFalse($loaded->relationLoaded('timeSlot'));
        $this->assertArrayNotHasKey('user_id', $loaded->getAttributes());
        $this->assertArrayNotHasKey('time_slot_id', $loaded->getAttributes());
        $this->assertArrayNotHasKey('patient_national_code', $loaded->getAttributes());
        $this->assertArrayNotHasKey('fee_rial', $loaded->getAttributes());
        $this->assertSame(
            ['id', 'appointment_id', 'status'],
            array_keys($loaded->latestPayment->getAttributes())
        );
        $this->assertSame(['id', 'name'], array_keys($loaded->clinic->getAttributes()));
        $this->assertSame(['id', 'title'], array_keys($loaded->visitReason->getAttributes()));
        $this->assertSame(['id', 'title'], array_keys($loaded->surgeryType->getAttributes()));
    }
}
