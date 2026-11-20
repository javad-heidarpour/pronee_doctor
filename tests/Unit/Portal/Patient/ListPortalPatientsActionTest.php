<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\ListPortalPatientsAction;
use App\Models\Appointment;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class ListPortalPatientsActionTest extends TestCase
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

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('patient_name')->nullable();
            $table->string('patient_phone')->nullable();
            $table->string('patient_national_code')->nullable();
            $table->string('status')->default('pending');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->timestamps();
        });
    }

    public function test_it_groups_patient_visits_and_selects_the_latest_visit_by_date_not_id(): void
    {
        $doctor = $this->doctor();

        $latestByDate = $this->appointment($doctor, 10, '2026-11-20', '09:30');
        $this->appointment($doctor, 10, '2026-11-10', '14:00');

        // A different clinic must not leak into the current user's directory scope.
        $this->appointment($doctor, 20, '2026-11-25', '10:00');

        $patients = app(ListPortalPatientsAction::class)->handle($doctor, [10]);

        $this->assertSame(1, $patients->total());
        $patient = $patients->items()[0];

        $this->assertSame(2, (int) $patient->appointments_count);
        $this->assertSame($latestByDate->id, (int) $patient->latest_appointment_id);
        $this->assertSame('2026-11-20', (string) $patient->last_appointment_date);
    }

    public function test_it_searches_by_patient_identity_fields(): void
    {
        $doctor = $this->doctor();
        $this->appointment($doctor, 10, '2026-11-20', '09:30', [
            'patient_name' => 'آزاده محمدی',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
        ]);
        $this->appointment($doctor, 10, '2026-11-21', '10:30', [
            'patient_name' => 'رضا احمدی',
            'patient_phone' => '09121111111',
            'patient_national_code' => '0098765432',
        ]);

        $patients = app(ListPortalPatientsAction::class)->handle($doctor, [10], '0012345678');

        $this->assertSame(1, $patients->total());
        $this->assertSame('آزاده محمدی', $patients->items()[0]->patient_name);
    }

    public function test_phone_only_patient_does_not_inherit_a_visit_from_another_patient_sharing_the_phone(): void
    {
        $doctor = $this->doctor();

        $phoneOnlyVisit = $this->appointment($doctor, 10, '2026-11-20', '09:30', [
            'patient_name' => 'Phone Only Patient',
            'patient_phone' => '09129999999',
            'patient_national_code' => null,
        ]);

        $codedPatientVisit = $this->appointment($doctor, 10, '2026-11-25', '10:30', [
            'patient_name' => 'National Code Patient',
            'patient_phone' => '09129999999',
            'patient_national_code' => '0012345678',
        ]);

        $patients = app(ListPortalPatientsAction::class)->handle($doctor, [10]);

        $phoneOnlyPatient = collect($patients->items())->firstWhere('patient_key', '09129999999');
        $codedPatient = collect($patients->items())->firstWhere('patient_key', '0012345678');

        $this->assertNotNull($phoneOnlyPatient);
        $this->assertNotNull($codedPatient);
        $this->assertSame($phoneOnlyVisit->id, (int) $phoneOnlyPatient->latest_appointment_id);
        $this->assertSame($codedPatientVisit->id, (int) $codedPatient->latest_appointment_id);
    }

    private function doctor(): Doctor
    {
        return Doctor::query()->create([
            'name' => 'Patient Directory Test Doctor',
            'slug' => 'patient-directory-test-doctor',
        ]);
    }

    private function appointment(
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
        ], $overrides));
    }
    public function test_it_groups_visits_by_stable_patient_id_when_identity_fields_change(): void
    {
        $doctor = $this->doctor();

        $this->appointment($doctor, 10, '2026-11-10', '09:30', [
            'patient_id' => 77,
            'patient_name' => 'نام قدیمی',
            'patient_phone' => '09120000000',
            'patient_national_code' => null,
        ]);
        $latestVisit = $this->appointment($doctor, 10, '2026-11-20', '11:30', [
            'patient_id' => 77,
            'patient_name' => 'نام به‌روزشده',
            'patient_phone' => '09129999999',
            'patient_national_code' => '0012345678',
        ]);

        $patients = app(ListPortalPatientsAction::class)->handle($doctor, [10]);

        $this->assertSame(1, $patients->total());
        $patient = $patients->items()[0];
        $this->assertSame(77, (int) $patient->patient_id);
        $this->assertSame(2, (int) $patient->appointments_count);
        $this->assertSame($latestVisit->id, (int) $patient->latest_appointment_id);
        $this->assertSame('نام به‌روزشده', $patient->patient_name);
        $this->assertSame('09129999999', $patient->patient_phone);
        $this->assertSame('0012345678', $patient->patient_national_code);
    }

}
