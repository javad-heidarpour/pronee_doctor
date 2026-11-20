<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientFollowUpController;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
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
                        $table->softDeletes();
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
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('doctor_user', function (Blueprint $table): void {
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('user_id');
            $table->json('permissions')->nullable();
            $table->json('clinic_ids')->nullable();
            $table->json('base_permissions')->nullable();
            $table->json('base_clinic_ids')->nullable();
            $table->boolean('managed_by_organization')->default(false);
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

        Schema::create('patient_treatment_plans', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->unsignedBigInteger('clinic_id');
            $table->unsignedBigInteger('patient_id');
            $table->string('title');
            $table->string('status')->default('proposed');
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
            'patient_treatment_plans',
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

    public function test_doctor_can_create_and_disable_a_follow_up_template_without_deleting_it(): void
    {
        $this->authenticateDoctor(11, 99);

        $createdAudit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $createdAudit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up_template.created',
                Mockery::type(PatientFollowUpTemplate::class),
                ['doctor_id' => 11]
            );

        $response = (new PatientFollowUpController())->storeTemplate(
            Request::create('/portal/follow-ups/templates', 'POST', [
                'title' => 'پیگیری پس از عمل',
                'category' => 'post_op',
                'description' => 'بررسی وضعیت بیمار پس از عمل',
                'default_days' => 7,
                'contact_method' => 'call',
                'send_sms' => true,
            ]),
            $createdAudit
        );

        $this->assertSame(302, $response->getStatusCode());
        $template = PatientFollowUpTemplate::query()->where('doctor_id', 11)->firstOrFail();
        $this->assertSame('پیگیری پس از عمل', $template->title);
        $this->assertTrue($template->send_sms);

        $disabledAudit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $disabledAudit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up_template.disabled',
                Mockery::type(PatientFollowUpTemplate::class),
                ['doctor_id' => 11]
            );

        $disabledResponse = (new PatientFollowUpController())->destroyTemplate($template->id, $disabledAudit);

        $this->assertSame(302, $disabledResponse->getStatusCode());
        $this->assertDatabaseHas('patient_follow_up_templates', [
            'id' => $template->id,
            'doctor_id' => 11,
            'is_active' => false,
        ]);
    }

    public function test_doctor_cannot_update_another_doctors_follow_up_template(): void
    {
        $this->authenticateDoctor(11, 99);
        $template = PatientFollowUpTemplate::query()->create([
            'doctor_id' => 12,
            'title' => 'الگوی پزشک دیگر',
            'category' => 'general',
            'default_days' => 3,
            'contact_method' => 'call',
            'send_sms' => false,
            'is_active' => true,
        ]);

        $this->expectException(\\Illuminate\\Database\\Eloquent\\ModelNotFoundException::class);

        (new PatientFollowUpController())->updateTemplate(
            Request::create('/portal/follow-ups/templates/'.$template->id, 'PUT', [
                'title' => 'تغییر غیرمجاز',
                'category' => 'general',
                'description' => null,
                'default_days' => 3,
                'contact_method' => 'call',
                'send_sms' => false,
            ]),
            $template->id,
            Mockery::mock(RecordSecurityAuditEventAction::class)
        );
    }

    public function test_secretary_cannot_create_or_manage_follow_up_templates(): void
    {
        $this->authenticateDoctor(11, 98, 'secretary');

        $this->expectException(\\Symfony\\Component\\HttpKernel\\Exception\\HttpException::class);

        (new PatientFollowUpController())->storeTemplate(
            Request::create('/portal/follow-ups/templates', 'POST', [
                'title' => 'الگوی غیرمجاز',
                'category' => 'general',
                'default_days' => 3,
                'contact_method' => 'call',
                'send_sms' => false,
            ]),
            Mockery::mock(RecordSecurityAuditEventAction::class)
        );
    }

    private function authenticateDoctor(int $doctorId, int $userId, string $role = 'doctor'): void
    {
        DB::table('doctors')->insert([
            'id' => $doctorId,
            'user_id' => $userId,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('addresses')->insert([
            'id' => 31 + ($doctorId - 11),
            'doctor_id' => $doctorId,
            'name' => 'Test Clinic',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = new User();
        $user->id = $userId;
        $user->role = $role;
        $user->is_active = true;
        Auth::guard('web')->setUser($user);
        session(['active_doctor_id' => $doctorId]);
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
}
