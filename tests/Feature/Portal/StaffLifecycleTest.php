<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\StaffAttendanceController;
use App\Http\Controllers\Portal\StaffManagementController;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class StaffLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-20 08:00:00');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('family')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->string('phone')->nullable();
            $table->string('role')->default('user');
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name')->nullable();
            $table->string('slug')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->string('name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('clinic_organizations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('owner_user_id');
            $table->string('name');
            $table->string('slug');
            $table->string('timezone')->nullable();
            $table->string('status')->default('active');
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_organization_doctors', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('doctor_id');
            $table->string('role')->default('owner');
            $table->integer('revenue_share_basis_points')->default(7000);
            $table->string('status')->default('active');
            $table->date('joined_at')->nullable();
            $table->timestamps();
            $table->primary(['organization_id', 'doctor_id']);
        });

        Schema::create('clinic_organization_clinics', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->string('branch_code')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->primary(['organization_id', 'clinic_id']);
        });

        Schema::create('clinic_organization_members', function (Blueprint $table): void {
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role')->nullable();
            $table->text('permissions')->nullable();
            $table->text('clinic_ids')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->primary(['organization_id', 'user_id']);
        });

        Schema::create('clinic_staff_contracts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->string('compensation_type');
            $table->unsignedBigInteger('base_monthly_salary')->default(0);
            $table->unsignedBigInteger('hourly_rate')->default(0);
            $table->unsignedInteger('standard_monthly_minutes');
            $table->unsignedInteger('overtime_multiplier_basis_points');
            $table->decimal('annual_leave_days', 5, 2);
            $table->json('paid_leave_types');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_staff_work_schedules', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->unsignedTinyInteger('weekday');
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('grace_minutes')->default(10);
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_staff_shift_replacements', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('absent_staff_user_id')->nullable();
            $table->unsignedBigInteger('replacement_staff_user_id');
            $table->date('work_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('clinic_staff_attendances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->date('work_date');
            $table->timestamp('check_in_at')->nullable();
            $table->timestamp('check_out_at')->nullable();
            $table->string('status')->nullable();
            $table->unsignedInteger('minutes_late')->default(0);
            $table->unsignedInteger('minutes_worked')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('clinic_staff_attendance_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('attendance_id');
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('event_type');
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('clinic_staff_leave_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->string('leave_type');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default('pending');
            $table->text('reason');
            $table->text('decision_note')->nullable();
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_owner_can_create_a_new_contract_and_close_the_previous_contract(): void
    {
        [$owner, $doctorId, $organizationId, $clinicId] = $this->ownedOrganization();
        $staff = $this->user('secretary');
        DB::table('clinic_organization_members')->insert([
            'organization_id' => $organizationId,
            'user_id' => $staff->id,
            'role' => 'receptionist',
            'permissions' => json_encode(['staff.attendance.view']),
            'clinic_ids' => json_encode([$clinicId]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('clinic_staff_contracts')->insert([
            'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'staff_user_id' => $staff->id,
            'compensation_type' => 'monthly', 'base_monthly_salary' => 20000000,
            'standard_monthly_minutes' => 10000, 'overtime_multiplier_basis_points' => 15000,
            'annual_leave_days' => 26, 'paid_leave_types' => json_encode(['annual']),
            'effective_from' => '2026-01-01', 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        app(StaffManagementController::class)->storeContract(Request::create('/portal/staff-management/contracts', 'POST', [
            'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'staff_user_id' => $staff->id,
            'compensation_type' => 'hourly', 'hourly_rate' => 500000,
            'standard_monthly_minutes' => 10000, 'overtime_multiplier_basis_points' => 15000,
            'annual_leave_days' => 20, 'paid_leave_types' => ['annual', 'sick'],
            'effective_from' => '2026-12-01', 'notes' => 'Renewed contract',
        ]));

        $this->assertDatabaseHas('clinic_staff_contracts', [
            'organization_id' => $organizationId, 'staff_user_id' => $staff->id,
            'effective_from' => '2026-12-01', 'compensation_type' => 'hourly', 'is_active' => true,
        ]);
        $this->assertDatabaseHas('clinic_staff_contracts', [
            'organization_id' => $organizationId, 'staff_user_id' => $staff->id,
            'effective_from' => '2026-01-01', 'effective_until' => '2026-11-30', 'is_active' => false,
        ]);
    }

    public function test_staff_check_in_and_check_out_are_recorded_once_with_worked_minutes_and_audit_events(): void
    {
        [$owner, $doctorId, $organizationId, $clinicId] = $this->ownedOrganization();
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        app(StaffAttendanceController::class)->checkIn(Request::create('/portal/staff-attendance/check-in', 'POST', [
            'organization_id' => $organizationId, 'clinic_id' => $clinicId,
        ]));

        $attendance = DB::table('clinic_staff_attendances')->first();
        $this->assertNotNull($attendance);
        $this->assertSame('present', $attendance->status);
        $this->assertDatabaseHas('clinic_staff_attendance_events', [
            'attendance_id' => $attendance->id, 'event_type' => 'check_in',
        ]);

        Carbon::setTestNow('2026-11-20 16:30:00');
        app(StaffAttendanceController::class)->checkOut(Request::create('/portal/staff-attendance/check-out', 'POST', [
            'organization_id' => $organizationId, 'clinic_id' => $clinicId,
        ]));

        $this->assertDatabaseHas('clinic_staff_attendances', [
            'id' => $attendance->id, 'status' => 'completed', 'minutes_worked' => 510,
        ]);
        $this->assertDatabaseHas('clinic_staff_attendance_events', [
            'attendance_id' => $attendance->id, 'event_type' => 'check_out',
        ]);
    }

    public function test_staff_leave_request_is_scoped_to_membership_and_manager_can_decide_it(): void
    {
        [$owner, $doctorId, $organizationId, $clinicId] = $this->ownedOrganization();
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        app(StaffAttendanceController::class)->storeLeave(Request::create('/portal/staff-attendance/leaves', 'POST', [
            'organization_id' => $organizationId, 'clinic_id' => $clinicId,
            'leave_type' => 'sick', 'start_date' => '2026-11-20', 'end_date' => '2026-11-21',
            'reason' => 'Medical leave request',
        ]));

        $leave = DB::table('clinic_staff_leave_requests')->first();
        $this->assertSame('pending', $leave->status);

        app(StaffAttendanceController::class)->decideLeave(Request::create('/portal/staff-attendance/leaves/'.$leave->id.'/decision', 'PATCH', [
            'decision' => 'approved', 'decision_note' => 'Reviewed by organization owner',
        ]), (int) $leave->id);

        $this->assertDatabaseHas('clinic_staff_leave_requests', [
            'id' => $leave->id, 'status' => 'approved', 'decided_by' => $owner->id,
        ]);
    }

    private function ownedOrganization(): array
    {
        $owner = $this->user('doctor');
        $doctorId = DB::table('doctors')->insertGetId([
            'user_id' => $owner->id, 'name' => 'Owner Doctor', 'slug' => 'owner-'.uniqid(),
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $organizationId = DB::table('clinic_organizations')->insertGetId([
            'owner_user_id' => $owner->id, 'name' => 'Test Organization', 'slug' => 'org-'.uniqid(),
            'timezone' => 'UTC', 'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_doctors')->insert([
            'organization_id' => $organizationId, 'doctor_id' => $doctorId,
            'role' => 'owner', 'status' => 'active', 'joined_at' => '2026-01-01',
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId, 'organization_id' => $organizationId, 'name' => 'Main clinic',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_clinics')->insert([
            'organization_id' => $organizationId, 'clinic_id' => $clinicId,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return [$owner, $doctorId, $organizationId, $clinicId];
    }

    private function user(string $role): User
    {
        $id = DB::table('users')->insertGetId([
            'name' => ucfirst($role), 'email' => $role.'-'.uniqid().'@example.test',
            'role' => $role, 'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);

        return User::query()->findOrFail($id);
    }
}
