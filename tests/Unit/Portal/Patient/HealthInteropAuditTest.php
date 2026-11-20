<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\HealthInteropController;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Mockery;
use Tests\TestCase;

final class HealthInteropAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('health_exchange_messages', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('destination');
            $table->string('event_type');
            $table->string('status');
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->string('remote_tracking_id')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_insurance_verifications', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('payer_code');
            $table->string('payer_name')->nullable();
            $table->string('status');
            $table->dateTime('checked_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_insurance_verifications');
        Schema::dropIfExists('health_exchange_messages');

        parent::tearDown();
    }

    public function test_health_interop_dashboard_access_is_audited_without_insurance_or_exchange_payloads(): void
    {
        $doctor = new Doctor();
        $doctor->id = 11;

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        $user->setRelation('doctorProfile', $doctor);
        Auth::guard('web')->setUser($user);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('health_interop.dashboard.viewed', null, ['doctor_id' => 11]);

        $response = (new HealthInteropController())->index($audit);

        $this->assertInstanceOf(Response::class, $response);
    }
}
