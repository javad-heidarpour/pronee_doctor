<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\BackfillPatientsFromAppointmentsAction;
use App\Models\Doctor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class BackfillPatientsFromAppointmentsActionTest extends TestCase
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
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone')->nullable();
            $table->string('patient_national_code')->nullable();
            $table->timestamps();
        });
    }

    public function test_it_links_legacy_visits_to_one_doctor_scoped_patient_and_is_safe_to_rerun(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Backfill Doctor',
            'slug' => 'backfill-doctor',
            'is_active' => true,
        ]);

        $firstAppointmentId = $this->appointment($doctor, 'بیمار قدیمی', '۰۹۱۲۱۲۳۴۵۶۷', '۰۰۱۲۳۴۵۶۷۸');
        $secondAppointmentId = $this->appointment($doctor, 'نام به‌روزشده', '09121234567', '0012345678');
        $emptyIdentityAppointmentId = $this->appointment($doctor, 'بدون شناسه', null, null);

        $action = app(BackfillPatientsFromAppointmentsAction::class);
        $firstRun = $action->handle(2);

        $this->assertSame(['processed' => 3, 'linked' => 2, 'skipped' => 1], $firstRun);

        $firstPatientId = DB::table('appointments')->where('id', $firstAppointmentId)->value('patient_id');
        $secondPatientId = DB::table('appointments')->where('id', $secondAppointmentId)->value('patient_id');

        $this->assertNotNull($firstPatientId);
        $this->assertSame((int) $firstPatientId, (int) $secondPatientId);
        $this->assertNull(DB::table('appointments')->where('id', $emptyIdentityAppointmentId)->value('patient_id'));
        $this->assertDatabaseCount('patients', 1);
        $this->assertDatabaseHas('patients', [
            'id' => $firstPatientId,
            'doctor_id' => $doctor->id,
            'phone' => '09121234567',
            'national_code' => '0012345678',
        ]);

        $secondRun = $action->handle(2);
        $this->assertSame(['processed' => 1, 'linked' => 0, 'skipped' => 1], $secondRun);
        $this->assertDatabaseCount('patients', 1);
    }

    private function appointment(
        Doctor $doctor,
        string $name,
        ?string $phone,
        ?string $nationalCode
    ): int {
        return (int) DB::table('appointments')->insertGetId([
            'doctor_id' => $doctor->id,
            'patient_id' => null,
            'patient_name' => $name,
            'patient_phone' => $phone,
            'patient_national_code' => $nationalCode,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
