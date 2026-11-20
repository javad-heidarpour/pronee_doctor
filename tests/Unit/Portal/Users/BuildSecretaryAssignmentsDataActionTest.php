<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Users;

use App\Actions\Portal\Users\BuildSecretaryAssignmentsDataAction;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class BuildSecretaryAssignmentsDataActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->string('remember_token')->nullable();
            $table->softDeletes();
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
            $table->text('permissions')->nullable();
            $table->text('clinic_ids')->nullable();
            $table->timestamps();
            $table->primary(['doctor_id', 'user_id']);
        });
    }

    public function test_doctor_cannot_build_assignments_for_another_doctor(): void
    {
        $activeDoctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $clinic = $this->clinic($otherDoctor);

        try {
            app(BuildSecretaryAssignmentsDataAction::class)->handle([
                'doctors' => [[
                    'id' => $otherDoctor->id,
                    'permissions' => ['appointments.view'],
                    'clinic_ids' => [$clinic->id],
                ]],
            ], 'doctor', $activeDoctor);

            $this->fail('A doctor must not assign a secretary to another doctor.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }

    public function test_assignment_rejects_a_clinic_owned_by_another_doctor(): void
    {
        $doctor = $this->doctor();
        $otherDoctor = $this->doctor();
        $foreignClinic = $this->clinic($otherDoctor);

        try {
            app(BuildSecretaryAssignmentsDataAction::class)->handle([
                'doctors' => [[
                    'id' => $doctor->id,
                    'permissions' => ['appointments.view'],
                    'clinic_ids' => [$foreignClinic->id],
                ]],
            ], 'admin', null);

            $this->fail('A clinic outside the selected doctor must be rejected.');
        } catch (HttpException $exception) {
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_assignment_rejects_inactive_doctors_and_inactive_doctor_accounts(): void
    {
        $inactiveProfile = $this->doctor(active: false);
        $inactiveAccountProfile = $this->doctor(userActive: false);

        foreach ([$inactiveProfile, $inactiveAccountProfile] as $doctor) {
            $clinic = $this->clinic($doctor);

            try {
                app(BuildSecretaryAssignmentsDataAction::class)->handle([
                    'doctors' => [[
                        'id' => $doctor->id,
                        'permissions' => ['appointments.view'],
                        'clinic_ids' => [$clinic->id],
                    ]],
                ], 'admin', null);

                $this->fail('Inactive doctor profiles and accounts must be rejected.');
            } catch (HttpException $exception) {
                $this->assertSame(422, $exception->getStatusCode());
            }
        }
    }

    public function test_admin_assignment_data_is_normalized_for_the_pivot(): void
    {
        $doctor = $this->doctor();
        $clinic = $this->clinic($doctor);

        $result = app(BuildSecretaryAssignmentsDataAction::class)->handle([
            'doctors' => [[
                'id' => (string) $doctor->id,
                'permissions' => ['appointments.view', 'appointments.manage'],
                'clinic_ids' => [(string) $clinic->id],
            ]],
        ], 'admin', null);

        $this->assertSame([
            'permissions' => json_encode(
                ['appointments.view', 'appointments.manage'],
                JSON_UNESCAPED_UNICODE
            ),
            'clinic_ids' => json_encode(
                [$clinic->id],
                JSON_UNESCAPED_UNICODE
            ),
        ], $result[$doctor->id]);
    }

    private function doctor(bool $active = true, bool $userActive = true): Doctor
    {
        $user = User::query()->create([
            'name' => 'Doctor account',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => 'doctor',
            'is_active' => $userActive,
        ]);

        return Doctor::query()->create([
            'user_id' => $user->id,
            'name' => 'Test Doctor',
            'is_active' => $active,
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
}
