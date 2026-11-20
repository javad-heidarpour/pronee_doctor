<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\ResolveOrCreatePatientAction;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ResolveOrCreatePatientActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
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
            $table->index(['doctor_id', 'national_code']);
            $table->index(['doctor_id', 'phone']);
        });
    }

    public function test_it_normalizes_persian_digits_and_international_phone_prefix(): void
    {
        $doctor = $this->doctor();

        $patient = app(ResolveOrCreatePatientAction::class)->handle(
            $doctor,
            '  بیمار آزمایشی  ',
            '+۹۸ ۹۱۲-۳۴۵-۶۷۸۹',
            '۰۰۱۲۳۴۵۶۷۸'
        );

        $this->assertNotNull($patient);
        $this->assertSame('بیمار آزمایشی', $patient->name);
        $this->assertSame('09123456789', $patient->phone);
        $this->assertSame('0012345678', $patient->national_code);
    }

    public function test_it_reuses_and_updates_patient_by_doctor_scoped_national_code(): void
    {
        $doctor = $this->doctor();
        $action = app(ResolveOrCreatePatientAction::class);

        $first = $action->handle($doctor, 'نام اول', '09120000000', '0012345678');
        $second = $action->handle($doctor, 'نام جدید', '09129999999', '0012345678');

        $this->assertSame($first->id, $second->id);
        $this->assertSame('نام جدید', $second->name);
        $this->assertSame('09129999999', $second->phone);
        $this->assertDatabaseCount('patients', 1);
    }

    public function test_it_reuses_phone_only_identity_without_merging_into_a_national_code_patient(): void
    {
        $doctor = $this->doctor();
        $action = app(ResolveOrCreatePatientAction::class);

        $knownPatient = $action->handle($doctor, 'بیمار دارای کد', '09121111111', '0012345678');
        $phoneOnlyPatient = $action->handle($doctor, 'بیمار بدون کد', '09121111111', null);
        $reusedPhoneOnlyPatient = $action->handle($doctor, 'نام به‌روزشده', '۰۹۱۲۱۱۱۱۱۱۱', null);

        $this->assertNotSame($knownPatient->id, $phoneOnlyPatient->id);
        $this->assertSame($phoneOnlyPatient->id, $reusedPhoneOnlyPatient->id);
        $this->assertSame('نام به‌روزشده', $reusedPhoneOnlyPatient->name);
        $this->assertNull($reusedPhoneOnlyPatient->national_code);
        $this->assertDatabaseCount('patients', 2);
    }

    public function test_patient_identity_is_isolated_between_doctors(): void
    {
        $firstDoctor = $this->doctor();
        $secondDoctor = $this->doctor();
        $action = app(ResolveOrCreatePatientAction::class);

        $firstPatient = $action->handle($firstDoctor, 'بیمار', '09120000000', '0012345678');
        $secondPatient = $action->handle($secondDoctor, 'بیمار', '09120000000', '0012345678');

        $this->assertNotSame($firstPatient->id, $secondPatient->id);
        $this->assertSame($firstDoctor->id, $firstPatient->doctor_id);
        $this->assertSame($secondDoctor->id, $secondPatient->doctor_id);
        $this->assertDatabaseCount('patients', 2);
    }

    public function test_it_returns_null_when_no_patient_identity_is_provided(): void
    {
        $doctor = $this->doctor();

        $patient = app(ResolveOrCreatePatientAction::class)->handle($doctor, 'بیمار', null, null);

        $this->assertNull($patient);
        $this->assertDatabaseCount('patients', 0);
    }

    private function doctor(): Doctor
    {
        $unique = Str::lower(Str::random(10));

        return Doctor::query()->create([
            'name' => 'پزشک ' . $unique,
            'slug' => 'doctor-' . $unique,
            'is_active' => true,
        ]);
    }
}
