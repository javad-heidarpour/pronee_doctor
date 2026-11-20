<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Appointment\BuildPatientIndexOptionsAction;
use App\Actions\Portal\Appointment\ListPortalAppointmentsAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientController;
use App\Http\Requests\Portal\ListPortalAppointmentsRequest;
use App\Models\Doctor;
use Inertia\Response;
use Mockery;
use Tests\TestCase;

final class PatientAppointmentListAuditTest extends TestCase
{
    public function test_appointment_list_access_is_audited_without_search_or_patient_values(): void
    {
        $doctor = new Doctor();
        $doctor->id = 11;

        $controller = new class extends PatientController {
            public Doctor $testDoctor;

            protected function currentDoctor(): Doctor
            {
                return $this->testDoctor;
            }

            protected function authorizeSecretaryPermission(string $permission, ?Doctor $doctor = null): void
            {
            }

            protected function accessibleClinicIds(?Doctor $doctor = null): array
            {
                return [31];
            }
        };
        $controller->testDoctor = $doctor;

        $request = Mockery::mock(ListPortalAppointmentsRequest::class);
        $request->shouldReceive('validated')->once()->andReturn(['status' => 'confirmed', 'search' => 'private patient term']);

        $appointments = Mockery::mock(ListPortalAppointmentsAction::class);
        $appointments->shouldReceive('handle')
            ->once()
            ->with($doctor, [31], ['status' => 'confirmed', 'search' => 'private patient term'])
            ->andReturn(['appointments' => collect(), 'stats' => []]);

        $options = Mockery::mock(BuildPatientIndexOptionsAction::class);
        $options->shouldReceive('handle')
            ->once()
            ->with($doctor, [31])
            ->andReturn(['clinics' => collect(), 'visitReasons' => collect(), 'surgeryTypes' => collect()]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('appointments.listed', null, ['doctor_id' => 11]);

        $response = $controller->index($request, $appointments, $options, $audit);

        $this->assertInstanceOf(Response::class, $response);
    }
}
