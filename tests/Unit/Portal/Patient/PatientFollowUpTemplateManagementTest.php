<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientFollowUpController;
use App\Models\Doctor;
use App\Models\PatientFollowUpTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

final class PatientFollowUpTemplateManagementTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('patient_follow_up_templates', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('doctor_id');
            $table->string('title', 160);
            $table->string('category', 60);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('default_days')->default(3);
            $table->string('contact_method', 20);
            $table->boolean('send_sms')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('patient_follow_up_templates');
        parent::tearDown();
    }

    public function test_doctor_can_create_a_template_with_audited_owner_and_defaults(): void
    {
        $this->authenticateAs('doctor');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up_template.created',
                Mockery::type(PatientFollowUpTemplate::class),
                ['doctor_id' => 55]
            );

        $response = $this->controller()->storeTemplate(
            Request::create('/portal/follow-ups/templates', 'POST', [
                'title' => 'پیگیری آزمایش',
                'category' => 'lab',
                'description' => 'بررسی انجام آزمایش و دریافت نتیجه',
                'default_days' => 7,
                'contact_method' => 'call',
                'send_sms' => false,
            ]),
            $audit
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('patient_follow_up_templates', [
            'doctor_id' => 55,
            'title' => 'پیگیری آزمایش',
            'category' => 'lab',
            'default_days' => 7,
            'contact_method' => 'call',
            'is_active' => true,
        ]);
    }

    public function test_doctor_can_update_only_their_own_template(): void
    {
        $this->authenticateAs('doctor');
        $templateId = $this->createTemplate(55, 'الگوی قبلی');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up_template.updated',
                Mockery::type(PatientFollowUpTemplate::class),
                ['doctor_id' => 55]
            );

        $response = $this->controller()->updateTemplate(
            Request::create('/portal/follow-ups/templates/' . $templateId, 'PUT', [
                'title' => 'الگوی به‌روزشده',
                'category' => 'chronic',
                'description' => 'پیگیری دوره‌ای',
                'default_days' => 30,
                'contact_method' => 'review',
                'send_sms' => false,
            ]),
            $templateId,
            $audit
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('patient_follow_up_templates', [
            'id' => $templateId,
            'doctor_id' => 55,
            'title' => 'الگوی به‌روزشده',
            'default_days' => 30,
        ]);
    }

    public function test_disabling_a_template_preserves_the_record_and_audits_the_change(): void
    {
        $this->authenticateAs('doctor');
        $templateId = $this->createTemplate(55, 'الگوی قابل نگهداری');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with(
                'patient.follow_up_template.disabled',
                Mockery::type(PatientFollowUpTemplate::class),
                ['doctor_id' => 55]
            );

        $response = $this->controller()->destroyTemplate($templateId, $audit);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertDatabaseHas('patient_follow_up_templates', [
            'id' => $templateId,
            'doctor_id' => 55,
            'is_active' => false,
        ]);
        $this->assertDatabaseCount('patient_follow_up_templates', 1);
    }

    public function test_doctor_cannot_update_another_doctors_template(): void
    {
        $this->authenticateAs('doctor');
        $templateId = $this->createTemplate(56, 'الگوی پزشک دیگر');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->expectException(ModelNotFoundException::class);

        $this->controller()->updateTemplate(
            Request::create('/portal/follow-ups/templates/' . $templateId, 'PUT', [
                'title' => 'تغییر غیرمجاز',
                'category' => 'general',
                'default_days' => 3,
                'contact_method' => 'call',
            ]),
            $templateId,
            $audit
        );
    }

    public function test_secretary_cannot_manage_doctor_templates(): void
    {
        $this->authenticateAs('secretary');
        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldNotReceive('handle');

        $this->expectException(HttpException::class);

        $this->controller()->storeTemplate(
            Request::create('/portal/follow-ups/templates', 'POST', [
                'title' => 'الگوی غیرمجاز',
                'category' => 'general',
                'default_days' => 3,
                'contact_method' => 'call',
            ]),
            $audit
        );
    }

    private function controller(): PatientFollowUpController
    {
        $doctor = new Doctor();
        $doctor->id = 55;

        return new class($doctor) extends PatientFollowUpController {
            public function __construct(private Doctor $testDoctor)
            {
            }

            protected function currentDoctor(): Doctor
            {
                return $this->testDoctor;
            }

            protected function authorizeSecretaryPermission(string $permission, ?Doctor $doctor = null): void
            {
            }
        };
    }

    private function authenticateAs(string $role): void
    {
        $user = new User();
        $user->id = 99;
        $user->role = $role;
        Auth::guard('web')->setUser($user);
    }

    private function createTemplate(int $doctorId, string $title): int
    {
        return DB::table('patient_follow_up_templates')->insertGetId([
            'doctor_id' => $doctorId,
            'title' => $title,
            'category' => 'general',
            'default_days' => 3,
            'contact_method' => 'call',
            'send_sms' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
