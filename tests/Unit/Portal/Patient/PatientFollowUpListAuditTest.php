<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\CreatePatientFollowUpAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientFollowUpController;
use App\Models\PatientFollowUpTask;
use App\Http\Requests\Portal\StorePatientFollowUpRequest;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

final class PatientFollowUpListAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->nullable();
            $table->string('family')->nullable();
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->boolean('is_active')->default(true);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('addresses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('patient_id')->nullable();
            $table->unsignedBigInteger('clinic_id');
            $table->date('appointment_date')->nullable();
            $table->time('appointment_time')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('patient_follow_up_tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->unsignedBigInteger('appointment_id')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->uuid('plan_uuid')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('due_at');
            $table->string('priority');
            $table->string('contact_method')->nullable();
            $table->boolean('send_sms')->default(false);
            $table->boolean('sms_consent_confirmed')->default(false);
            $table->string('status');
            $table->string('outcome')->nullable();
            $table->text('result_notes')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('sms_sent_at')->nullable();
            $table->dateTime('sms_attempted_at')->nullable();
            $table->unsignedSmallInteger('sms_attempts')->default(0);
            $table->text('last_sms_error')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_follow_up_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('title');
            $table->string('category');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_days')->default(0);
            $table->string('contact_method');
            $table->boolean('send_sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        foreach ([
            'patient_follow_up_templates',
            'patient_follow_up_tasks',
            'appointments',
            'patients',
            'doctor_user',
            'addresses',
            'doctors',
            'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        parent::tearDown();
    }

    public function test_follow_up_list_access_is_audited_without_task_or_patient_details(): void
    {
        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 99,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('addresses')->insert([
            'id' => 31,
            'doctor_id' => 11,
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => 11]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.follow_ups.listed', null, ['doctor_id' => 11]);

        $response = (new PatientFollowUpController())->index(
            Request::create('/portal/follow-ups', 'GET'),
            $audit
        );

        $this->assertInstanceOf(Response::class, $response);
    }


    public function test_secretary_without_clinical_view_receives_only_workflow_follow_up_data(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.follow_ups.listed', null, ['doctor_id' => 11]);

        $request = Request::create('/portal/follow-ups', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $response = (new PatientFollowUpController())->index($request, $audit);
        $page = $response->toResponse($request)->getData(true);
        $task = $page['props']['tasks']['data'][0];

        $this->assertFalse($page['props']['canViewClinicalNotes']);
        $this->assertSame('پیگیری بیمار', $task['title']);
        $this->assertArrayNotHasKey('description', $task);
        $this->assertArrayNotHasKey('result_notes', $task);
        $this->assertArrayNotHasKey('last_sms_error', $task);
        $this->assertArrayNotHasKey('plan_uuid', $task);
        $this->assertTrue($task['sms_failed']);
        $this->assertSame([], $page['props']['templates']);
        $this->assertArrayNotHasKey('pivot', $page['props']['secretaries'][0]);
    }

    public function test_secretary_with_clinical_view_can_read_follow_up_notes_but_not_provider_errors(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view', 'patients.clinical.view']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('patient.follow_ups.listed', null, ['doctor_id' => 11]);

        $request = Request::create('/portal/follow-ups', 'GET');
        $request->headers->set('X-Inertia', 'true');

        $response = (new PatientFollowUpController())->index($request, $audit);
        $page = $response->toResponse($request)->getData(true);
        $task = $page['props']['tasks']['data'][0];

        $this->assertTrue($page['props']['canViewClinicalNotes']);
        $this->assertSame('Diagnosis-specific follow-up', $task['title']);
        $this->assertSame('Sensitive clinical follow-up detail', $task['description']);
        $this->assertSame('Sensitive outcome detail', $task['result_notes']);
        $this->assertArrayNotHasKey('last_sms_error', $task);
        $this->assertArrayNotHasKey('plan_uuid', $task);
    }

    public function test_secretary_without_clinical_view_cannot_write_free_text_follow_up_results(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view', 'patients.manage']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up.updated',
                Mockery::type(PatientFollowUpTask::class),
                [
                    'doctor_id' => 11,
                    'status_changed' => true,
                    'changed_fields' => ['status', 'outcome'],
                ]
            );

        $request = Request::create('/portal/follow-ups/61', 'PUT', [
            'status' => 'completed',
            'outcome' => 'reached',
            'result_notes' => 'Unauthorized free-text clinical detail',
        ]);

        (new PatientFollowUpController())->update($request, 61, $audit);

        $this->assertSame(
            'Sensitive outcome detail',
            DB::table('patient_follow_up_tasks')->where('id', 61)->value('result_notes')
        );
    }


    public function test_patient_and_appointment_context_filters_limit_follow_up_results(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view']);
        $now = now();

        DB::table('patients')->insert([
            'id' => 42,
            'doctor_id' => 11,
            'name' => 'Second Patient',
            'phone' => '09121111111',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('appointments')->insert([
            [
                'id' => 52,
                'doctor_id' => 11,
                'patient_id' => 41,
                'clinic_id' => 31,
                'appointment_date' => today()->addDay()->toDateString(),
                'appointment_time' => '10:00:00',
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 53,
                'doctor_id' => 11,
                'patient_id' => 42,
                'clinic_id' => 31,
                'appointment_date' => today()->addDays(2)->toDateString(),
                'appointment_time' => '11:00:00',
                'status' => 'confirmed',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        DB::table('patient_follow_up_tasks')->insert([
            [
                'id' => 62,
                'doctor_id' => 11,
                'clinic_id' => 31,
                'patient_id' => 41,
                'appointment_id' => 52,
                'assigned_to' => 7,
                'created_by' => 7,
                'plan_uuid' => 'c5c7cde8-0a84-4c1b-b3e7-d75f7f77b4a9',
                'title' => 'Second appointment follow-up',
                'due_at' => $now->copy()->addDays(2),
                'priority' => 'normal',
                'contact_method' => 'call',
                'send_sms' => false,
                'sms_consent_confirmed' => false,
                'status' => 'pending',
                'sms_attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 63,
                'doctor_id' => 11,
                'clinic_id' => 31,
                'patient_id' => 42,
                'appointment_id' => 53,
                'assigned_to' => 7,
                'created_by' => 7,
                'plan_uuid' => 'd6d8dee9-1b95-4d2c-c4f8-e86a8a88c5b0',
                'title' => 'Another patient follow-up',
                'due_at' => $now->copy()->addDays(3),
                'priority' => 'normal',
                'contact_method' => 'call',
                'send_sms' => false,
                'sms_consent_confirmed' => false,
                'status' => 'pending',
                'sms_attempts' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->twice()
            ->with('patient.follow_ups.listed', null, ['doctor_id' => 11]);

        $controller = new PatientFollowUpController();

        $appointmentRequest = Request::create('/portal/follow-ups', 'GET', [
            'patient_id' => 41,
            'appointment_id' => 51,
        ]);
        $appointmentRequest->headers->set('X-Inertia', 'true');
        $appointmentPage = $controller->index($appointmentRequest, $audit)
            ->toResponse($appointmentRequest)->getData(true)['props'];

        $this->assertSame(41, (int) $appointmentPage['selectedPatientId']);
        $this->assertSame(51, (int) $appointmentPage['selectedAppointmentId']);
        $this->assertSame([61], array_map('intval', array_column($appointmentPage['tasks']['data'], 'id')));

        $patientRequest = Request::create('/portal/follow-ups', 'GET', ['patient_id' => 41]);
        $patientRequest->headers->set('X-Inertia', 'true');
        $patientPage = $controller->index($patientRequest, $audit)
            ->toResponse($patientRequest)->getData(true)['props'];

        $this->assertSame([61, 62], array_map('intval', array_column($patientPage['tasks']['data'], 'id')));
    }

    public function test_appointment_filter_requires_a_patient_context(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view']);

        $request = Request::create('/portal/follow-ups', 'GET', ['appointment_id' => 51]);
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->expectException(ValidationException::class);

        (new PatientFollowUpController())->index($request, $audit);
    }

    private function seedSecretaryFollowUpTask(array $permissions): void
    {
        $now = now();

        DB::table('doctors')->insert([
            'id' => 11,
            'user_id' => 99,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('addresses')->insert([
            'id' => 31,
            'doctor_id' => 11,
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('users')->insert([
            'id' => 7,
            'name' => 'Test Secretary',
            'family' => 'Operator',
            'role' => 'secretary',
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('doctor_user')->insert([
            'doctor_id' => 11,
            'user_id' => 7,
            'permissions' => json_encode($permissions),
            'clinic_ids' => json_encode([31]),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('patients')->insert([
            'id' => 41,
            'doctor_id' => 11,
            'name' => 'Test Patient',
            'phone' => '09120000000',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('appointments')->insert([
            'id' => 51,
            'doctor_id' => 11,
            'patient_id' => 41,
            'clinic_id' => 31,
            'appointment_date' => today()->toDateString(),
            'appointment_time' => '09:00:00',
            'status' => 'confirmed',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('patient_follow_up_tasks')->insert([
            'id' => 61,
            'doctor_id' => 11,
            'clinic_id' => 31,
            'patient_id' => 41,
            'appointment_id' => 51,
            'assigned_to' => 7,
            'created_by' => 7,
            'plan_uuid' => 'b4b6bde7-9f73-4b0b-a2df-c64e6a66a3a8',
            'title' => 'Diagnosis-specific follow-up',
            'description' => 'Sensitive clinical follow-up detail',
            'due_at' => $now->copy()->addDay(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'send_sms' => true,
            'sms_consent_confirmed' => true,
            'status' => 'pending',
            'outcome' => null,
            'result_notes' => 'Sensitive outcome detail',
            'completed_at' => null,
            'sms_sent_at' => null,
            'sms_attempted_at' => $now,
            'sms_attempts' => 2,
            'last_sms_error' => 'Provider error containing private response details',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Auth::guard('web')->setUser(User::query()->findOrFail(7));
        session(['active_doctor_id' => 11]);
    }

    public function test_secretary_with_view_only_permission_cannot_write_follow_up_result_notes(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view', 'patients.manage', 'patients.clinical.view']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up.updated',
                Mockery::type(PatientFollowUpTask::class),
                [
                    'doctor_id' => 11,
                    'status_changed' => true,
                    'changed_fields' => ['status', 'outcome'],
                ]
            );

        $request = Request::create('/portal/follow-ups/61', 'PUT', [
            'status' => 'completed',
            'outcome' => 'reached',
            'result_notes' => 'Unauthorized view-only clinical update',
        ]);

        (new PatientFollowUpController())->update($request, 61, $audit);

        $this->assertSame(
            'Sensitive outcome detail',
            DB::table('patient_follow_up_tasks')->where('id', 61)->value('result_notes')
        );
    }

    public function test_secretary_with_clinical_manage_permission_can_write_follow_up_result_notes(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view', 'patients.manage', 'patients.clinical.manage']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up.updated',
                Mockery::type(PatientFollowUpTask::class),
                [
                    'doctor_id' => 11,
                    'status_changed' => true,
                    'changed_fields' => ['status', 'outcome', 'result_notes'],
                ]
            );

        $request = Request::create('/portal/follow-ups/61', 'PUT', [
            'status' => 'completed',
            'outcome' => 'reached',
            'result_notes' => 'Authorized clinical outcome',
        ]);

        (new PatientFollowUpController())->update($request, 61, $audit);

        $this->assertSame(
            'Authorized clinical outcome',
            DB::table('patient_follow_up_tasks')->where('id', 61)->value('result_notes')
        );
    }


    public function test_secretary_without_clinical_write_permission_cannot_create_free_text_follow_up_descriptions(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view', 'patients.manage']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up.created',
                Mockery::type(PatientFollowUpTask::class),
                [
                    'doctor_id' => 11,
                    'changed_fields' => ['follow_up_created'],
                ]
            );

        $request = Mockery::mock(StorePatientFollowUpRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'patient_id' => 41,
            'appointment_id' => 51,
            'title' => 'پیگیری بعد از مراجعه',
            'description' => 'Unauthorized clinical free text',
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'steps' => [[
                'title' => 'تماس',
                'days' => 0,
                'contact_method' => 'call',
            ]],
        ]);

        (new PatientFollowUpController())->store(
            $request,
            app(CreatePatientFollowUpAction::class),
            $audit
        );

        $this->assertDatabaseHas('patient_follow_up_tasks', [
            'doctor_id' => 11,
            'patient_id' => 41,
            'description' => null,
        ]);
    }

    public function test_secretary_with_clinical_write_permission_can_create_follow_up_descriptions(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view', 'patients.manage', 'patients.clinical.manage']);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up.created',
                Mockery::type(PatientFollowUpTask::class),
                [
                    'doctor_id' => 11,
                    'changed_fields' => ['follow_up_created'],
                ]
            );

        $request = Mockery::mock(StorePatientFollowUpRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'patient_id' => 41,
            'appointment_id' => 51,
            'title' => 'پیگیری بعد از مراجعه',
            'description' => 'Authorized clinical follow-up detail',
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'steps' => [[
                'title' => 'تماس',
                'days' => 0,
                'contact_method' => 'call',
            ]],
        ]);

        (new PatientFollowUpController())->store(
            $request,
            app(CreatePatientFollowUpAction::class),
            $audit
        );

        $this->assertDatabaseHas('patient_follow_up_tasks', [
            'doctor_id' => 11,
            'patient_id' => 41,
            'description' => 'Authorized clinical follow-up detail',
        ]);
    }

    public function test_secretary_cannot_select_a_hidden_doctor_follow_up_template(): void
    {
        $this->seedSecretaryFollowUpTask(['patients.view', 'patients.manage', 'patients.clinical.manage']);

        $request = Mockery::mock(StorePatientFollowUpRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([
            'patient_id' => 41,
            'appointment_id' => 51,
            'template_id' => 5,
            'title' => null,
            'due_at' => now()->addDay()->toDateTimeString(),
            'priority' => 'normal',
            'contact_method' => 'call',
            'steps' => [[
                'title' => 'تماس',
                'days' => 0,
                'contact_method' => 'call',
            ]],
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        (new PatientFollowUpController())->store(
            $request,
            app(CreatePatientFollowUpAction::class),
            app(RecordSecurityAuditEventAction::class)
        );
    }

}
