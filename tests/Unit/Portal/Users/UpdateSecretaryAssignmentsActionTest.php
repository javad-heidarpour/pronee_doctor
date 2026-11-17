<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Users;

use App\Actions\Portal\Users\UpdateSecretaryAssignmentsAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class UpdateSecretaryAssignmentsActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('family')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->string('remember_token')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->timestamps();
            $table->primary(['doctor_id', 'user_id']);
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        config([
            'session.driver' => 'array',
            'session.table' => 'sessions',
        ]);
    }

    public function test_admin_can_save_two_clinics_and_revoke_existing_secretary_sessions(): void
    {
        config(['session.driver' => 'database']);

        $secretary = $this->user('secretary');
        $doctor = $this->doctor();
        $clinicOne = $this->clinic($doctor);
        $clinicTwo = $this->clinic($doctor);

        DB::table('sessions')->insert([
            'id' => 'secretary-session',
            'user_id' => $secretary->id,
            'payload' => 'session-payload',
            'last_activity' => time(),
        ]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        app(UpdateSecretaryAssignmentsAction::class)->handle(
            $secretary,
            [
                $doctor->id => $this->pivotData([$clinicOne->id, $clinicTwo->id]),
            ],
            'admin',
            null,
            [$doctor->id],
            $audit
        );

        $this->assertDatabaseHas('doctor_user', [
            'doctor_id' => $doctor->id,
            'user_id' => $secretary->id,
        ]);
        $this->assertNotEmpty($secretary->fresh()->remember_token);
        $this->assertDatabaseMissing('sessions', ['id' => 'secretary-session']);
    }

    public function test_admin_cannot_assign_more_than_two_clinics_to_a_secretary(): void
    {
        $secretary = $this->user('secretary');
        $doctor = $this->doctor();
        $clinics = [
            $this->clinic($doctor),
            $this->clinic($doctor),
            $this->clinic($doctor),
        ];

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->expectException(ValidationException::class);

        app(UpdateSecretaryAssignmentsAction::class)->handle(
            $secretary,
            [
                $doctor->id => $this->pivotData(array_map(
                    fn (Address $clinic) => $clinic->id,
                    $clinics
                )),
            ],
            'admin',
            null,
            [$doctor->id],
            $audit
        );
    }

    public function test_doctor_cannot_exceed_two_clinics_across_all_doctor_assignments(): void
    {
        $secretary = $this->user('secretary');
        $activeDoctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $otherClinic = $this->clinic($otherDoctor);
        $currentClinicOne = $this->clinic($activeDoctor);
        $currentClinicTwo = $this->clinic($activeDoctor);

        $secretary->doctors()->attach($otherDoctor->id, [
            'permissions' => json_encode(['appointments.view']),
            'clinic_ids' => json_encode([$otherClinic->id]),
        ]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->expectException(ValidationException::class);

        app(UpdateSecretaryAssignmentsAction::class)->handle(
            $secretary,
            [
                $activeDoctor->id => $this->pivotData([
                    $currentClinicOne->id,
                    $currentClinicTwo->id,
                ]),
            ],
            'doctor',
            $activeDoctor,
            [$activeDoctor->id],
            $audit
        );
    }

    public function test_doctor_can_update_only_own_assignment_without_removing_other_doctor_links(): void
    {
        $secretary = $this->user('secretary');
        $activeDoctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $otherClinic = $this->clinic($otherDoctor);
        $currentClinic = $this->clinic($activeDoctor);

        $secretary->doctors()->attach($otherDoctor->id, [
            'permissions' => json_encode(['appointments.view']),
            'clinic_ids' => json_encode([$otherClinic->id]),
        ]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once();

        app(UpdateSecretaryAssignmentsAction::class)->handle(
            $secretary,
            [
                $activeDoctor->id => $this->pivotData([$currentClinic->id]),
            ],
            'doctor',
            $activeDoctor,
            [$activeDoctor->id],
            $audit
        );

        $this->assertDatabaseHas('doctor_user', [
            'doctor_id' => $activeDoctor->id,
            'user_id' => $secretary->id,
        ]);
        $this->assertDatabaseHas('doctor_user', [
            'doctor_id' => $otherDoctor->id,
            'user_id' => $secretary->id,
        ]);
    }

    private function user(string $role): User
    {
        return User::query()->create([
            'name' => 'Test account',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'test-password',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function doctor(): Doctor
    {
        $user = $this->user('doctor');

        return Doctor::query()->create([
            'user_id' => $user->id,
            'name' => 'Test doctor',
            'slug' => 'test-doctor-' . uniqid(),
            'is_active' => true,
        ]);
    }

    private function clinic(Doctor $doctor): Address
    {
        return Address::query()->create([
            'doctor_id' => $doctor->id,
            'name' => 'Test clinic',
            'is_active' => true,
        ]);
    }

    private function pivotData(array $clinicIds): array
    {
        return [
            'permissions' => json_encode(['appointments.view', 'appointments.manage']),
            'clinic_ids' => json_encode($clinicIds),
        ];
    }
}
