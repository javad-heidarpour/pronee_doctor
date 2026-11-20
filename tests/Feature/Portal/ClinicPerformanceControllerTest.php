<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\ClinicPerformanceController;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class ClinicPerformanceControllerTest extends TestCase
{
    private User $owner;
    private Doctor $doctor;
    private int $clinicA;
    private int $clinicB;

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('appointment_id');
            $table->unsignedBigInteger('amount_rial');
            $table->string('status');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->date('date');
            $table->string('status');
            $table->timestamps();
        });

        $this->owner = User::query()->create([
            'name' => 'Clinic owner',
            'email' => 'performance-owner@example.test',
            'role' => 'doctor',
            'is_active' => true,
        ]);
        $this->doctor = Doctor::query()->create([
            'user_id' => $this->owner->id,
            'is_active' => true,
        ]);
        $this->clinicA = DB::table('addresses')->insertGetId([
            'doctor_id' => $this->doctor->id, 'name' => 'Clinic A', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->clinicB = DB::table('addresses')->insertGetId([
            'doctor_id' => $this->doctor->id, 'name' => 'Clinic B', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        Auth::guard('web')->setUser($this->owner);
        session(['active_doctor_id' => $this->doctor->id]);
    }

    public function test_performance_report_aggregates_only_the_selected_clinic_and_paid_revenue(): void
    {
        $date = today()->toDateString();
        $a1 = DB::table('appointments')->insertGetId([
            'doctor_id' => $this->doctor->id, 'clinic_id' => $this->clinicA,
            'appointment_date' => $date, 'appointment_time' => '09:00:00',
            'status' => 'visited', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('appointments')->insert([
            'doctor_id' => $this->doctor->id, 'clinic_id' => $this->clinicA,
            'appointment_date' => $date, 'appointment_time' => '10:00:00',
            'status' => 'cancelled', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('appointments')->insert([
            'doctor_id' => $this->doctor->id, 'clinic_id' => $this->clinicB,
            'appointment_date' => $date, 'appointment_time' => '11:00:00',
            'status' => 'pending', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('appointment_payments')->insert([
            'appointment_id' => $a1, 'amount_rial' => 250000, 'status' => 'paid',
            'paid_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('appointment_payments')->insert([
            'appointment_id' => $a1, 'amount_rial' => 900000, 'status' => 'failed',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('time_slots')->insert([
            ['doctor_id' => $this->doctor->id, 'clinic_id' => $this->clinicA, 'date' => $date, 'status' => 'booked', 'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $this->doctor->id, 'clinic_id' => $this->clinicA, 'date' => $date, 'status' => 'available', 'created_at' => now(), 'updated_at' => now()],
            ['doctor_id' => $this->doctor->id, 'clinic_id' => $this->clinicB, 'date' => $date, 'status' => 'booked', 'created_at' => now(), 'updated_at' => now()],
        ]);

        $response = app(ClinicPerformanceController::class)->index(Request::create('/portal/clinic-performance', 'GET', [
            'from' => $date, 'to' => $date, 'clinic_id' => $this->clinicA,
        ]));

        $this->assertInstanceOf(Response::class, $response);
        $props = $response->toResponse(request())->getData(true)['page']['props'];
        $this->assertSame(2, $props['report']['appointments']['total']);
        $this->assertSame(1, $props['report']['appointments']['served']);
        $this->assertSame(1, $props['report']['appointments']['cancelled']);
        $this->assertSame(250000, $props['report']['finance']['gross_paid_rial']);
        $this->assertSame(1, $props['report']['finance']['paid_appointments']);
        $this->assertSame(2, $props['report']['capacity']['total']);
        $this->assertSame(1, $props['report']['capacity']['occupied']);
        $this->assertCount(1, $props['report']['clinics']);
        $this->assertSame($this->clinicA, $props['report']['clinics'][0]['id']);
    }

    public function test_performance_report_rejects_a_clinic_outside_the_doctor_scope(): void
    {
        $foreignClinic = DB::table('addresses')->insertGetId([
            'doctor_id' => $this->doctor->id + 999, 'name' => 'Foreign clinic', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        try {
            app(ClinicPerformanceController::class)->index(Request::create('/portal/clinic-performance', 'GET', [
                'clinic_id' => $foreignClinic,
            ]));
            $this->fail('A clinic outside the active doctor scope must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_performance_report_rejects_reversed_date_ranges(): void
    {
        try {
            app(ClinicPerformanceController::class)->index(Request::create('/portal/clinic-performance', 'GET', [
                'from' => today()->addDay()->toDateString(),
                'to' => today()->toDateString(),
            ]));
            $this->fail('A reversed report range must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }
}
