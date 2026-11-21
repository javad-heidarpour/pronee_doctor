<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\LoadPortalPatientRecordAction;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class LoadPortalPatientRecordActionTest extends TestCase
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

        Schema::create('cities', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
        });

        Schema::create('sms_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->timestamp('created_at')->nullable();
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
            $table->string('patient_name')->nullable();
            $table->string('patient_phone')->nullable();
            $table->string('patient_national_code')->nullable();
            $table->string('status')->default('pending');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('visit_reason_id')->nullable();
            $table->unsignedBigInteger('surgery_type_id')->nullable();
            $table->date('surgery_date')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_loads_all_visits_only_for_the_same_patient_doctor_and_accessible_clinics(): void
    {
        $doctor = $this->doctor('doctor-one');
        $otherDoctor = $this->doctor('doctor-two');

        $reference = $this->visit($doctor, 10, '2026-11-17', '10:00');
        $samePatient = $this->visit($doctor, 20, '2026-11-10', '09:00');
        $this->visit($doctor, 30, '2026-11-25', '09:00');
        $this->visit($otherDoctor, 10, '2026-11-26', '09:00');

        $visits = app(LoadPortalPatientRecordAction::class)->handle($reference, [10, 20]);

        $this->assertSame(
            [$reference->id, $samePatient->id],
            $visits->pluck('id')->all()
        );
    }

    public function test_it_does_not_eager_load_a_patient_owned_by_another_doctor(): void
    {
        $doctor = $this->doctor('doctor-owner');
        $otherDoctor = $this->doctor('doctor-other-owner');
        $foreignPatient = \App\Models\Patient::query()->create([
            'doctor_id' => $otherDoctor->id,
            'name' => 'Foreign Patient',
            'phone' => '09121111111',
        ]);
        $reference = $this->visit($doctor, 10, '2026-11-17', '10:00', [
            'patient_id' => $foreignPatient->id,
        ]);

        $visits = app(LoadPortalPatientRecordAction::class)->handle($reference, [10]);

        $this->assertCount(1, $visits);
        $this->assertNull($visits->first()->patient);
    }

    public function test_it_does_not_group_legacy_visits_by_a_shared_phone_without_a_national_code(): void
    {
        $doctor = $this->doctor('doctor-phone');
        $reference = $this->visit($doctor, 10, '2026-11-17', '10:00', [
            'patient_id' => null,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);
        $this->visit($doctor, 10, '2026-11-10', '09:00', [
            'patient_id' => null,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);

        $visits = app(LoadPortalPatientRecordAction::class)->handle($reference, [10]);

        $this->assertSame([$reference->id], $visits->pluck('id')->all());
    }

    public function test_legacy_timeline_uses_national_code_only_for_unlinked_rows(): void
    {
        $doctor = $this->doctor('doctor-legacy-national-code');
        $reference = $this->visit($doctor, 10, '2026-11-17', '10:00', [
            'patient_id' => null,
            'patient_national_code' => '0012345678',
            'patient_phone' => '09120000000',
        ]);
        $matchingLegacy = $this->visit($doctor, 10, '2026-11-10', '09:00', [
            'patient_id' => null,
            'patient_national_code' => '0012345678',
            'patient_phone' => '09129999999',
        ]);
        $this->visit($doctor, 10, '2026-11-11', '09:00', [
            'patient_id' => 77,
            'patient_national_code' => '0012345678',
            'patient_phone' => '09120000000',
        ]);
        $this->visit($doctor, 10, '2026-11-12', '09:00', [
            'patient_id' => null,
            'patient_national_code' => '0099999999',
            'patient_phone' => '09120000000',
        ]);

        $visits = app(LoadPortalPatientRecordAction::class)->handle($reference, [10]);

        $this->assertSame([$reference->id, $matchingLegacy->id], $visits->pluck('id')->all());
    }

    public function test_appointment_details_history_uses_patient_id_instead_of_shared_phone(): void
    {
        $doctor = $this->doctor('doctor-appointment-details-identity');
        $reference = $this->visit($doctor, 10, '2026-11-17', '10:00', [
            'patient_id' => 101,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);
        $samePatient = $this->visit($doctor, 10, '2026-11-10', '09:00', [
            'patient_id' => 101,
            'patient_national_code' => null,
            'patient_phone' => '09128888888',
        ]);
        $this->visit($doctor, 10, '2026-11-11', '09:00', [
            'patient_id' => 202,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);

        $details = app(\App\Actions\Portal\Appointment\LoadPortalAppointmentDetailsAction::class)
            ->handle($reference, [10]);

        $this->assertSame([$samePatient->id], $details->patientHistory->pluck('id')->all());
    }

    public function test_legacy_appointment_details_do_not_use_phone_as_a_patient_identifier(): void
    {
        $doctor = $this->doctor('doctor-legacy-appointment-details');
        $reference = $this->visit($doctor, 10, '2026-11-17', '10:00', [
            'patient_id' => null,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);
        $this->visit($doctor, 10, '2026-11-10', '09:00', [
            'patient_id' => null,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);

        $details = app(\App\Actions\Portal\Appointment\LoadPortalAppointmentDetailsAction::class)
            ->handle($reference, [10]);

        $this->assertCount(0, $details->patientHistory);
    }

    public function test_it_prefers_the_stable_patient_id_when_loading_a_patient_timeline(): void
    {
        $doctor = $this->doctor('doctor-stable-patient');
        $patient = \App\Models\Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Updated Patient Name',
            'phone' => '09129999999',
            'national_code' => '0012345678',
        ]);

        $reference = $this->visit($doctor, 10, '2026-11-17', '10:00', [
            'patient_id' => $patient->id,
            'patient_phone' => '09120000000',
        ]);
        $matching = $this->visit($doctor, 10, '2026-11-10', '09:00', [
            'patient_id' => $patient->id,
            'patient_phone' => '09128888888',
            'patient_national_code' => null,
        ]);
        $this->visit($doctor, 10, '2026-11-11', '09:00', [
            'patient_id' => null,
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
        ]);

        $visits = app(LoadPortalPatientRecordAction::class)->handle($reference, [10]);

        $this->assertSame([$reference->id, $matching->id], $visits->pluck('id')->all());
    }

    private function doctor(string $slug): Doctor
    {
        return Doctor::query()->create([
            'name' => 'Test Doctor',
            'slug' => $slug,
        ]);
    }

    private function visit(
        Doctor $doctor,
        int $clinicId,
        string $date,
        string $time,
        array $overrides = []
    ): Appointment {
        return Appointment::query()->create(array_merge([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
            'status' => 'visited',
            'appointment_date' => $date,
            'appointment_time' => $time,
            'description' => 'Private visit note',
        ], $overrides));
    }
}
