<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\RecordPatientVitalSignAction;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class RecordPatientVitalSignActionTest extends TestCase
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

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone', 45)->nullable();
            $table->string('national_code', 20)->nullable();
            $table->timestamps();
        });

        Schema::create('patient_vital_signs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('measurement_type', 40);
            $table->decimal('value', 8, 2);
            $table->string('unit', 16);
            $table->dateTime('measured_at');
            $table->string('note', 1000)->nullable();
            $table->timestamps();
        });
    }


    public function test_it_rejects_values_outside_the_measurement_range(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'slug' => 'doctor-vitals-range']);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient',
            'phone' => '09120000000',
        ]);

        $this->expectException(ValidationException::class);

        app(RecordPatientVitalSignAction::class)->handle($patient, [
            'measurement_type' => 'oxygen_saturation',
            'value' => 120,
            'measured_at' => '2026-11-18 10:30:00',
        ]);
    }

    public function test_it_records_a_vital_sign_with_a_server_defined_unit(): void
    {
        $doctor = Doctor::query()->create(['name' => 'Doctor', 'slug' => 'doctor-vitals']);
        $patient = Patient::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Patient',
            'phone' => '09120000000',
        ]);

        $record = app(RecordPatientVitalSignAction::class)->handle($patient, [
            'measurement_type' => 'heart_rate',
            'value' => 78,
            'measured_at' => '2026-11-18 10:30:00',
            'note' => 'After resting',
            'unit' => 'unsafe-unit',
            'doctor_id' => 999,
        ]);

        $this->assertSame($patient->id, $record->patient_id);
        $this->assertSame($doctor->id, $record->doctor_id);
        $this->assertSame('bpm', $record->unit);
        $this->assertDatabaseHas('patient_vital_signs', [
            'id' => $record->id,
            'measurement_type' => 'heart_rate',
            'unit' => 'bpm',
        ]);
    }
}
