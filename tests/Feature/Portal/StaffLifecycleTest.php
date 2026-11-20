<?php

declare(strict_types=1);

namespace Tests\Feature\Portal;

use App\Http\Controllers\Portal\StaffAttendanceController;
use App\Http\Controllers\Portal\StaffManagementController;
use App\Http\Controllers\Portal\StaffResponsibilitiesController;
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

        Schema::create('clinic_staff_responsibilities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->string('title', 160);
            $table->text('description');
            $table->string('priority', 16)->default('normal');
            $table->string('status', 16)->default('active');
            $table->date('effective_from');
            $table->date('effective_until')->nullable();
            $table->date('review_at')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('clinic_staff_responsibility_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('responsibility_id');
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->string('event_type', 24);
            $table->text('old_values')->nullable();
            $table->text('new_values')->nullable();
            $table->string('reason', 500)->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('clinic_staff_payrolls', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id')->nullable();
            $table->unsignedBigInteger('staff_user_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('gross_amount')->default(0);
            $table->unsignedBigInteger('deduction_amount')->default(0);
            $table->unsignedBigInteger('net_amount')->default(0);
            $table->string('status')->default('draft');
            $table->timestamps();
        });

        Schema::create('clinic_staff_leave_balances', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->unsignedInteger('year');
            $table->decimal('entitled_days', 7, 2)->default(0);
            $table->decimal('carry_over_days', 7, 2)->default(0);
            $table->decimal('adjustment_days', 7, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('clinic_staff_obligations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('staff_user_id');
            $table->string('title')->nullable();
            $table->unsignedBigInteger('amount_rial')->default(0);
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
        });

        Schema::create('clinic_organization_holidays', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->date('holiday_date');
            $table->string('title')->nullable();
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

    public function test_staff_report_aggregates_late_and_incomplete_attendance_days(): void
    {
        [$owner, $doctorId, $organizationId, $clinicId] = $this->ownedOrganization();
        $staff = $this->user('secretary');
        DB::table('clinic_organization_members')->insert([
            'organization_id' => $organizationId, 'user_id' => $staff->id,
            'role' => 'receptionist', 'permissions' => json_encode(['staff.attendance.view']),
            'clinic_ids' => json_encode([$clinicId]), 'is_active' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_staff_attendances')->insert([
            [
                'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'staff_user_id' => $staff->id,
                'work_date' => '2026-11-19', 'check_in_at' => '2026-11-19 08:20:00',
                'check_out_at' => '2026-11-19 16:00:00', 'status' => 'late',
                'minutes_late' => 20, 'minutes_worked' => 440, 'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'staff_user_id' => $staff->id,
                'work_date' => '2026-11-20', 'check_in_at' => '2026-11-20 08:00:00',
                'check_out_at' => null, 'status' => 'present',
                'minutes_late' => 0, 'minutes_worked' => 120, 'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        $response = app(StaffManagementController::class)->index(Request::create('/portal/staff-management?month=2026-11', 'GET', ['month' => '2026-11']));
        $props = $response->toResponse(Request::create('/portal/staff-management', 'GET', [], [], [], ['HTTP_X_INERTIA' => 'true']))->getData(true)['props'];
        $row = collect($props['performance'])->firstWhere('staff_user_id', $staff->id);

        $this->assertNotNull($row);
        $this->assertSame(2, $row['recorded_days']);
        $this->assertSame(1, $row['late_days']);
        $this->assertSame(560, $row['worked_minutes']);
        $this->assertSame(1, $row['incomplete_days']);
        $this->assertSame(20, $row['late_minutes']);
    }

    public function test_capacity_report_suggests_only_a_free_member_assigned_to_the_same_clinic(): void
    {
        [$owner, $doctorId, $organizationId, $clinicId] = $this->ownedOrganization();
        $absent = $this->user('secretary');
        $candidate = $this->user('secretary');
        foreach ([$absent, $candidate] as $member) {
            DB::table('clinic_organization_members')->insert([
                'organization_id' => $organizationId, 'user_id' => $member->id,
                'role' => 'receptionist', 'permissions' => json_encode(['staff.attendance.view']),
                'clinic_ids' => json_encode([$clinicId]), 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        DB::table('clinic_staff_work_schedules')->insert([
            'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'staff_user_id' => $absent->id,
            'weekday' => Carbon::now('Asia/Tehran')->dayOfWeek, 'start_time' => '07:00:00', 'end_time' => '15:00:00',
            'break_minutes' => 30, 'grace_minutes' => 10, 'effective_from' => '2026-11-01',
            'is_active' => true, 'created_by' => $owner->id, 'created_at' => now(), 'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        $response = app(StaffManagementController::class)->index(Request::create('/portal/staff-management?month=2026-11', 'GET', ['month' => '2026-11']));
        $props = $response->toResponse(Request::create('/portal/staff-management', 'GET', [], [], [], ['HTTP_X_INERTIA' => 'true']))->getData(true)['props'];
        $suggestion = collect($props['coverageSuggestions'])->firstWhere('absent_staff_user_id', $absent->id);

        $this->assertNotNull($suggestion);
        $this->assertSame($clinicId, $suggestion['clinic_id']);
        $this->assertSame([$candidate->id], collect($suggestion['candidates'])->pluck('user_id')->all());
    }

    public function test_owner_can_create_update_and_archive_staff_responsibilities_with_audit_history(): void
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
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        $controller = app(StaffResponsibilitiesController::class);
        $payload = [
            'organization_id' => $organizationId,
            'clinic_id' => $clinicId,
            'staff_user_id' => $staff->id,
            'title' => 'پاسخ‌گویی و پذیرش',
            'description' => 'ثبت و پیگیری درخواست‌های مراجعه‌کنندگان و هماهنگی نوبت‌ها.',
            'priority' => 'important',
            'status' => 'active',
            'effective_from' => '2026-11-20',
            'effective_until' => '2027-11-19',
            'review_at' => '2027-02-20',
        ];

        $controller->store(Request::create('/portal/staff-responsibilities', 'POST', $payload));
        $responsibility = DB::table('clinic_staff_responsibilities')->first();

        $this->assertNotNull($responsibility);
        $this->assertSame('پاسخ‌گویی و پذیرش', $responsibility->title);
        $this->assertSame('active', $responsibility->status);
        $this->assertDatabaseHas('clinic_staff_responsibility_events', [
            'responsibility_id' => $responsibility->id,
            'event_type' => 'created',
            'actor_user_id' => $owner->id,
        ]);

        $payload['title'] = 'پذیرش و پیگیری مراجعان';
        $payload['status'] = 'paused';
        $controller->update(Request::create('/portal/staff-responsibilities/'.$responsibility->id, 'PUT', $payload), (int) $responsibility->id);

        $this->assertDatabaseHas('clinic_staff_responsibilities', [
            'id' => $responsibility->id,
            'title' => 'پذیرش و پیگیری مراجعان',
            'status' => 'paused',
            'updated_by' => $owner->id,
        ]);
        $this->assertDatabaseHas('clinic_staff_responsibility_events', [
            'responsibility_id' => $responsibility->id,
            'event_type' => 'updated',
        ]);

        $controller->destroy((int) $responsibility->id);
        $this->assertSoftDeleted('clinic_staff_responsibilities', ['id' => $responsibility->id]);
        $this->assertDatabaseHas('clinic_staff_responsibility_events', [
            'responsibility_id' => $responsibility->id,
            'event_type' => 'deleted',
        ]);
    }

    public function test_staff_responsibility_cannot_be_assigned_outside_the_members_clinic_scope(): void
    {
        [$owner, $doctorId, $organizationId, $clinicId] = $this->ownedOrganization();
        $otherClinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId, 'organization_id' => $organizationId, 'name' => 'Other clinic',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_clinics')->insert([
            'organization_id' => $organizationId, 'clinic_id' => $otherClinicId,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
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
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        app(StaffResponsibilitiesController::class)->store(Request::create('/portal/staff-responsibilities', 'POST', [
            'organization_id' => $organizationId,
            'clinic_id' => $otherClinicId,
            'staff_user_id' => $staff->id,
            'title' => 'مسئولیت خارج از محدوده',
            'description' => 'این تخصیص باید رد شود.',
            'priority' => 'normal',
            'status' => 'active',
            'effective_from' => '2026-11-20',
        ]));
    }

    public function test_staff_shift_overlap_is_rejected_even_when_the_second_shift_is_at_another_clinic(): void
    {
        [$owner, $doctorId, $organizationId, $clinicId] = $this->ownedOrganization();
        $otherClinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctorId, 'organization_id' => $organizationId, 'name' => 'Second clinic',
            'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('clinic_organization_clinics')->insert([
            'organization_id' => $organizationId, 'clinic_id' => $otherClinicId,
            'status' => 'active', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $staff = $this->user('secretary');
        DB::table('clinic_organization_members')->insert([
            'organization_id' => $organizationId,
            'user_id' => $staff->id,
            'role' => 'receptionist',
            'permissions' => json_encode(['staff.attendance.view']),
            'clinic_ids' => json_encode([$clinicId, $otherClinicId]),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Auth::guard('web')->setUser($owner);
        session(['active_doctor_id' => $doctorId]);

        $controller = app(StaffAttendanceController::class);
        $firstShift = [
            'organization_id' => $organizationId,
            'clinic_id' => $clinicId,
            'staff_user_id' => $staff->id,
            'weekday' => 1,
            'start_time' => '08:00',
            'end_time' => '12:00',
            'effective_from' => '2026-11-20',
        ];
        $controller->storeSchedule(Request::create('/portal/staff-attendance/schedules', 'POST', $firstShift));

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $controller->storeSchedule(Request::create('/portal/staff-attendance/schedules', 'POST', array_merge($firstShift, [
            'clinic_id' => $otherClinicId,
            'start_time' => '10:00',
            'end_time' => '14:00',
        ])));
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
