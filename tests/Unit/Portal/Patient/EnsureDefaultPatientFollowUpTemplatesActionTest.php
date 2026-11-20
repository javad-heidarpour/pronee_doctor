<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Patient\EnsureDefaultPatientFollowUpTemplatesAction;
use App\Models\Doctor;
use App\Models\PatientFollowUpTemplate;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class EnsureDefaultPatientFollowUpTemplatesActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('doctors', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('name');
            $table->string('family')->nullable();
            $table->string('slug')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

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
        Schema::dropIfExists('doctors');
        parent::tearDown();
    }

    public function test_it_creates_starter_templates_for_new_doctors_idempotently(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Doctor',
            'family' => 'Test',
            'slug' => 'doctor-template-test',
        ]);

        $action = app(EnsureDefaultPatientFollowUpTemplatesAction::class);
        $action->handle($doctor);

        $this->assertSame(6, PatientFollowUpTemplate::query()->where('doctor_id', $doctor->id)->count());
        $this->assertDatabaseHas('patient_follow_up_templates', [
            'doctor_id' => $doctor->id,
            'title' => 'یادآوری انجام آزمایش',
            'category' => 'lab',
            'default_days' => 7,
            'contact_method' => 'call',
            'is_active' => true,
        ]);

        $action->handle($doctor);

        $this->assertSame(6, PatientFollowUpTemplate::query()->where('doctor_id', $doctor->id)->count());
    }

    public function test_it_preserves_customized_and_disabled_starter_templates(): void
    {
        $doctor = Doctor::query()->create([
            'name' => 'Doctor',
            'family' => 'Test',
            'slug' => 'doctor-template-preserve',
        ]);

        $action = app(EnsureDefaultPatientFollowUpTemplatesAction::class);
        $action->handle($doctor);

        $template = PatientFollowUpTemplate::query()
            ->where('doctor_id', $doctor->id)
            ->where('title', 'پیگیری پس از ویزیت')
            ->firstOrFail();
        $template->update([
            'default_days' => 12,
            'description' => 'تنظیم اختصاصی پزشک',
            'is_active' => false,
        ]);

        $action->handle($doctor);

        $template->refresh();
        $this->assertSame(12, $template->default_days);
        $this->assertSame('تنظیم اختصاصی پزشک', $template->description);
        $this->assertFalse($template->is_active);
        $this->assertSame(6, PatientFollowUpTemplate::query()->where('doctor_id', $doctor->id)->count());
    }
}
