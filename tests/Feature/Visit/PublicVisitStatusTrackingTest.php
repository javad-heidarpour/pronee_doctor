<?php

declare(strict_types=1);

namespace Tests\Feature\Visit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

final class PublicVisitStatusTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->createVisitTrackingSchema();
    }

    protected function tearDown(): void
    {
        foreach ([
            'appointment_payments',
            'appointments',
            'doctor_holidays',
            'time_slots',
            'addresses',
            'doctors',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_signed_visit_page_shows_current_status_without_exposing_patient_phone(): void
    {
        $now = now();

        $userId = DB::table('users')->insertGetId([
            'name' => 'پزشک آزمایشی',
            'email' => 'visit-doctor@example.test',
            'password' => 'not-used',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $doctorId = DB::table('doctors')->insertGetId([
            'user_id' => $userId,
            'name' => 'نمونه',
            'family' => 'پزشکی',
            'slug' => 'sample-public-doctor',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'مطب مرکزی',
            'full_address' => 'نشانی آزمایشی',
            'phone' => '05100000000',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $slotId = DB::table('time_slots')->insertGetId([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'date' => $now->copy()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'booked',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
            'status' => 'pending',
            'appointment_date' => $now->copy()->addDay()->toDateString(),
            'appointment_time' => '10:00:00',
            'fee_rial' => 7000000,
            'reservation_expires_at' => $now->copy()->addMinutes(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $url = URL::temporarySignedRoute(
            'visit.show',
            $now->copy()->addMinutes(15),
            ['visit_number' => $appointmentId]
        );

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Visit/Show')
                ->where('visit.number', $appointmentId)
                ->where('visit.doctor_name', 'دکتر نمونه پزشکی')
                ->where('visit.appointment_status', 'pending')
                ->where('visit.appointment_status_label', 'در انتظار')
                ->where('visit.payment_status', 'unpaid')
                ->where('visit.clinic_name', 'مطب مرکزی')
                ->where('visit.clinic_address', 'نشانی آزمایشی')
                ->where('visit.clinic_phone', '05100000000')
                ->where('visit.patient_name', 'بیمار آزمایشی')
                ->missing('visit.patient_phone'));
    }

    public function test_patient_can_cancel_with_a_signed_link_and_persian_phone_digits(): void
    {
        [$appointmentId, $slotId] = $this->createAppointmentForCancellation('09120000000');

        $url = URL::temporarySignedRoute(
            'visit.cancel',
            now()->addMinutes(15),
            ['visit_number' => $appointmentId]
        );

        $this->from('/')
            ->post($url, ['patient_phone' => '۰۹۱۲۰۰۰۰۰۰۰'])
            ->assertRedirect('/');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => 'cancelled',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'id' => $slotId,
            'status' => 'available',
        ]);
    }

    public function test_patient_cannot_cancel_when_the_phone_does_not_match(): void
    {
        [$appointmentId, $slotId] = $this->createAppointmentForCancellation('09120000000');

        $url = URL::temporarySignedRoute(
            'visit.cancel',
            now()->addMinutes(15),
            ['visit_number' => $appointmentId]
        );

        $this->from('/')
            ->post($url, ['patient_phone' => '09121111111'])
            ->assertRedirect('/')
            ->assertSessionHasErrors('patient_phone');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'status' => 'pending',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'id' => $slotId,
            'status' => 'booked',
        ]);
    }

    public function test_reschedule_slots_shown_to_patient_respect_one_hour_cutoff(): void
    {
        [$appointmentId] = $this->createAppointmentForCancellation('09120000000');
        $appointment = DB::table('appointments')->where('id', $appointmentId)->first();

        $tooSoon = now()->addMinutes(30);
        $validTime = now()->addHours(2);

        DB::table('time_slots')->insert([
            [
                'doctor_id' => $appointment->doctor_id,
                'clinic_id' => $appointment->clinic_id,
                'date' => $tooSoon->toDateString(),
                'start_time' => $tooSoon->format('H:i:s'),
                'end_time' => $tooSoon->copy()->addMinutes(30)->format('H:i:s'),
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'doctor_id' => $appointment->doctor_id,
                'clinic_id' => $appointment->clinic_id,
                'date' => $validTime->toDateString(),
                'start_time' => $validTime->format('H:i:s'),
                'end_time' => $validTime->copy()->addMinutes(30)->format('H:i:s'),
                'status' => 'available',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $validSlotId = (int) DB::table('time_slots')
            ->where('doctor_id', $appointment->doctor_id)
            ->where('clinic_id', $appointment->clinic_id)
            ->where('date', $validTime->toDateString())
            ->where('start_time', $validTime->format('H:i:s'))
            ->value('id');

        $url = URL::temporarySignedRoute(
            'visit.show',
            now()->addMinutes(15),
            ['visit_number' => $appointmentId]
        );

        $this->get($url)
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('visit.available_slots.0.id', $validSlotId)
                ->missing('visit.available_slots.1'));
    }

    public function test_patient_can_reschedule_with_a_signed_link_and_persian_phone_digits(): void
    {
        [$appointmentId, $oldSlotId] = $this->createAppointmentForCancellation('09120000000');
        $appointment = DB::table('appointments')->where('id', $appointmentId)->first();
        $newDate = now()->copy()->addDays(2)->toDateString();

        $newSlotId = DB::table('time_slots')->insertGetId([
            'doctor_id' => $appointment->doctor_id,
            'clinic_id' => $appointment->clinic_id,
            'date' => $newDate,
            'start_time' => '11:00:00',
            'end_time' => '11:30:00',
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $url = URL::temporarySignedRoute(
            'visit.reschedule',
            now()->addMinutes(15),
            ['visit_number' => $appointmentId]
        );

        $this->from('/')
            ->post($url, [
                'patient_phone' => '۰۹۱۲۰۰۰۰۰۰۰',
                'new_time_slot_id' => $newSlotId,
            ])
            ->assertRedirect('/')
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'id' => $appointmentId,
            'time_slot_id' => $newSlotId,
            'appointment_date' => $newDate,
        ]);
        $storedAppointmentTime = (string) DB::table('appointments')
            ->where('id', $appointmentId)
            ->value('appointment_time');
        $this->assertSame('11:00', substr($storedAppointmentTime, 0, 5));
        $this->assertDatabaseHas('time_slots', [
            'id' => $oldSlotId,
            'status' => 'available',
        ]);
        $this->assertDatabaseHas('time_slots', [
            'id' => $newSlotId,
            'status' => 'booked',
        ]);
    }

    private function createAppointmentForCancellation(string $patientPhone): array
    {
        $now = now();

        $userId = DB::table('users')->insertGetId([
            'name' => 'پزشک آزمایشی',
            'email' => 'cancel-doctor-' . uniqid() . '@example.test',
            'password' => 'not-used',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $doctorId = DB::table('doctors')->insertGetId([
            'user_id' => $userId,
            'name' => 'نمونه',
            'family' => 'پزشکی',
            'slug' => 'sample-cancel-doctor-' . uniqid(),
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId,
            'name' => 'مطب مرکزی',
            'full_address' => 'نشانی آزمایشی',
            'phone' => '05100000000',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $date = $now->copy()->addDay()->toDateString();

        $slotId = DB::table('time_slots')->insertGetId([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'date' => $date,
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'booked',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $appointmentId = DB::table('appointments')->insertGetId([
            'doctor_id' => $doctorId,
            'clinic_id' => $clinicId,
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => $patientPhone,
            'status' => 'pending',
            'appointment_date' => $date,
            'appointment_time' => '10:00:00',
            'fee_rial' => 7000000,
            'reservation_expires_at' => $now->copy()->addMinutes(10),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return [$appointmentId, $slotId];
    }

    private function createVisitTrackingSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->string('name');
            $table->string('family')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->string('name');
            $table->text('full_address')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('time_slots', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->foreignId('clinic_id');
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('doctor_holidays', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->foreignId('clinic_id');
            $table->date('holiday_date');
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('doctor_id');
            $table->foreignId('clinic_id');
            $table->foreignId('time_slot_id')->nullable();
            $table->string('patient_name');
            $table->string('patient_phone');
            $table->string('status');
            $table->date('appointment_date');
            $table->time('appointment_time');
            $table->unsignedBigInteger('fee_rial')->default(0);
            $table->dateTime('reservation_expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('appointment_id');
            $table->string('status');
            $table->timestamps();
        });
    }
}
