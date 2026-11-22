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
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
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

    public function test_phone_only_legacy_visits_are_not_grouped_by_a_shared_household_phone(): void
    {
        $doctor = $this->doctor();
        $first = $this->appointment($doctor, 10, '2026-11-20', '09:30', [
            'patient_id' => null,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);
        $second = $this->appointment($doctor, 10, '2026-11-21', '10:30', [
            'patient_id' => null,
            'patient_national_code' => null,
            'patient_phone' => '09120000000',
        ]);

        $patients = app(ListPortalPatientsAction::class)->handle($doctor, [10]);

        $this->assertSame(2, $patients->total());
        $this->assertSame(
            ['appointment:' . $first->id, 'appointment:' . $second->id],
            collect($patients->items())->pluck('patient_key')->sort()->values()->all()
        );
        $this->assertSame([1, 1], collect($patients->items())
            ->pluck('appointments_count')->map(fn ($count) => (int) $count)->sort()->values()->all());
        $this->assertSame(
            [$first->id, $second->id],
            collect($patients->items())->pluck('latest_appointment_id')->sort()->values()->all()
        );
    }

    public function test_patient_id_precedes_national_code_and_legacy_rows_remain_separate(): void
    {
        $doctor = $this->doctor();
        $linkedPatientVisit = $this->appointment($doctor, 10, '2026-11-10', '09:00', [
            'patient_id' => 101,
            'patient_national_code' => '0012345678',
        ]);
        $latestLinkedPatientVisit = $this->appointment($doctor, 10, '2026-11-20', '09:00', [
            'patient_id' => 101,
            'patient_national_code' => null,
        ]);
        $otherLinkedPatientVisit = $this->appointment($doctor, 10, '2026-11-19', '09:00', [
            'patient_id' => 202,
            'patient_national_code' => '0012345678',
        ]);
        $legacyVisit = $this->appointment($doctor, 10, '2026-11-18', '09:00', [
            'patient_id' => null,
            'patient_national_code' => '0012345678',
        ]);

        $patients = app(ListPortalPatientsAction::class)->handle($doctor, [10]);
        $rows = collect($patients->items())->keyBy('patient_key');

        $this->assertSame(3, $patients->total());
        $this->assertSame(2, (int) $rows['patient:101']->appointments_count);
        $this->assertSame($latestLinkedPatientVisit->id, (int) $rows['patient:101']->latest_appointment_id);
        $this->assertSame(1, (int) $rows['patient:202']->appointments_count);
        $this->assertSame($otherLinkedPatientVisit->id, (int) $rows['patient:202']->latest_appointment_id);
        $this->assertSame(1, (int) $rows['national_code:0012345678']->appointments_count);
        $this->assertSame($legacyVisit->id, (int) $rows['national_code:0012345678']->latest_appointment_id);
        $this->assertNotSame($linkedPatientVisit->id, $latestLinkedPatientVisit->id);
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
}
