<?php

declare(strict_types=1);

namespace Tests\Unit\Visit;

use App\Actions\Visit\ShowVisitAction;
use App\Models\Appointment;
use App\Models\AppointmentPayment;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ShowVisitActionReceiptTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id(); $table->string('name')->nullable(); $table->string('email')->nullable();
            $table->string('password')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('doctors', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable(); $table->string('family')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('addresses', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->string('name')->nullable();
            $table->string('full_address')->nullable(); $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('appointments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('doctor_id'); $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('time_slot_id')->nullable(); $table->unsignedBigInteger('user_id')->nullable();
            $table->string('patient_name'); $table->string('patient_phone'); $table->string('status');
            $table->date('appointment_date'); $table->time('appointment_time');
            $table->unsignedBigInteger('fee_rial')->default(0); $table->timestamp('reservation_expires_at')->nullable();
            $table->timestamps();
        });
        Schema::create('appointment_payments', function (Blueprint $table): void {
            $table->id(); $table->unsignedBigInteger('appointment_id'); $table->unsignedBigInteger('amount_rial');
            $table->string('status'); $table->string('payment_uuid')->unique(); $table->string('gateway_name')->default('sep');
            $table->string('ref_num')->nullable(); $table->timestamp('paid_at')->nullable();
            $table->timestamp('gateway_verified_at')->nullable(); $table->timestamps();
        });
    }

    public function test_receipt_is_exposed_only_for_a_successfully_verified_payment(): void
    {
        [$appointment] = $this->appointment();
        AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id, 'amount_rial' => 12500000,
            'status' => 'initiated', 'payment_uuid' => (string) Str::uuid(), 'gateway_name' => 'sep',
        ]);

        $result = app(ShowVisitAction::class)->handle($appointment->id);

        $this->assertSame('initiated', $result['payment_status']);
        $this->assertNull($result['payment_receipt']);
    }

    public function test_receipt_uses_verified_paid_attempt_even_if_a_later_attempt_failed(): void
    {
        [$appointment] = $this->appointment();
        $paid = AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id, 'amount_rial' => 12500000,
            'status' => 'paid', 'payment_uuid' => (string) Str::uuid(), 'gateway_name' => 'sep',
            'ref_num' => 'SEP-REF-123', 'paid_at' => now()->subMinutes(5),
            'gateway_verified_at' => now()->subMinutes(5),
        ]);
        AppointmentPayment::query()->create([
            'appointment_id' => $appointment->id, 'amount_rial' => 12500000,
            'status' => 'failed', 'payment_uuid' => (string) Str::uuid(), 'gateway_name' => 'sep',
        ]);

        $result = app(ShowVisitAction::class)->handle($appointment->id);

        $this->assertSame('paid', $result['payment_status']);
        $this->assertSame('PRX-' . str_pad((string) $paid->id, 8, '0', STR_PAD_LEFT), $result['payment_receipt']['receipt_number']);
        $this->assertSame(12500000, $result['payment_receipt']['amount_rial']);
        $this->assertSame(1250000, $result['payment_receipt']['amount_toman']);
        $this->assertSame('SEP-REF-123', $result['payment_receipt']['reference']);
        $this->assertSame('سامان‌کیش', $result['payment_receipt']['gateway']);
        $this->assertNotNull($result['payment_receipt']['paid_at']);
    }

    private function appointment(): array
    {
        $doctorId = DB::table('doctors')->insertGetId([
            'name' => 'Test', 'family' => 'Doctor', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId, 'name' => 'Main clinic', 'full_address' => 'Test address',
            'phone' => '02100000000', 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $appointment = Appointment::query()->create([
            'doctor_id' => $doctorId, 'clinic_id' => $clinicId,
            'patient_name' => 'Test Patient', 'patient_phone' => '09120000000',
            'status' => 'completed', 'appointment_date' => '2026-11-19',
            'appointment_time' => '09:00:00', 'fee_rial' => 12500000,
        ]);

        return [$appointment];
    }
}
