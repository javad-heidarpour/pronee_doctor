<?php

declare(strict_types=1);

namespace Tests\Unit\Security;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\SecurityAuditLog;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

final class RecordSecurityAuditEventActionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('security_audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('event', 100);
            $table->string('subject_type', 150)->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
        });
    }

    public function test_audit_metadata_excludes_patient_identifiers_and_clinical_values(): void
    {
        app(RecordSecurityAuditEventAction::class)->handle(
            'patient.portal.health.viewed',
            null,
            [
                'patient_id' => 42,
                'national_code' => '0012345678',
                'phone' => '09120000000',
                'diagnosis' => 'Sensitive diagnosis',
                'doctor_id' => 7,
                'program_id' => 8,
                'scenario_id' => 9,
                'inventory_item_id' => 10,
            ]
        );

        $log = SecurityAuditLog::query()->firstOrFail();

        $this->assertSame('patient.portal.health.viewed', $log->event);
        $this->assertSame([
            'doctor_id' => 7,
            'program_id' => 8,
            'scenario_id' => 9,
            'inventory_item_id' => 10,
        ], $log->metadata);
        $this->assertArrayNotHasKey('patient_id', $log->metadata);
        $this->assertArrayNotHasKey('diagnosis', $log->metadata);
    }
}
