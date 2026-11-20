<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Appointment\UpdateAppointmentDescriptionAction;
use App\Actions\Portal\Appointment\UpdateAppointmentStatusAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\DTOs\Portal\Appointment\UpdateAppointmentDescriptionData;
use App\Http\Controllers\Portal\PatientController;
use App\Http\Requests\Portal\UpdateAppointmentDescriptionRequest;
use App\Http\Requests\Portal\UpdateAppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Mockery;
use Tests\TestCase;

final class PatientAppointmentAuditTest extends TestCase
{
    private function controller(): PatientController
    {
        $doctor = new Doctor();
        $doctor->id = 11;

        $controller = new class extends PatientController {
            public Doctor $testDoctor;

            protected function currentDoctor(): Doctor
            {
                return $this->testDoctor;
            }

            protected function accessibleClinicIds(?Doctor $doctor = null): array
            {
                return [31];
            }
        };

        $controller->testDoctor = $doctor;

        $user = new User();
        $user->id = 99;
        $user->role = 'doctor';
        Auth::guard('web')->setUser($user);

        return $controller;
    }

    private function appointment(): Appointment
    {
        $appointment = new Appointment();
        $appointment->id = 501;
        $appointment->doctor_id = 11;
        $appointment->clinic_id = 31;

        return $appointment;
    }

    public function test_status_change_is_audited_without_patient_or_visit_values(): void
    {
        $controller = $this->controller();
        $appointment = $this->appointment();

        $request = Mockery::mock(UpdateAppointmentStatusRequest::class);
        $request->shouldReceive('validated')->once()->with('status')->andReturn('cancelled');

        $action = Mockery::mock(UpdateAppointmentStatusAction::class);
        $action->shouldReceive('handle')->once()->with($appointment, 'cancelled')->andReturn('updated');

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once()->with(
            'appointment.status.updated',
            $appointment,
            ['doctor_id' => 11, 'status_changed' => true]
        );

        $response = $controller->updateStatus($request, $appointment, $action, $audit);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_visit_details_change_audits_field_names_but_never_clinical_values(): void
    {
        $controller = $this->controller();
        $appointment = $this->appointment();
        $clinicalText = 'private diagnosis and treatment details';
        $validated = ['description' => $clinicalText];

        $request = Mockery::mock(UpdateAppointmentDescriptionRequest::class);
        $request->shouldReceive('validated')->once()->andReturn($validated);

        $action = Mockery::mock(UpdateAppointmentDescriptionAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with(
                $appointment,
                Mockery::on(fn ($data): bool => $data instanceof UpdateAppointmentDescriptionData
                    && $data->description === $clinicalText)
            )
            ->andReturn(true);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')->once()->with(
            'appointment.visit_details.updated',
            $appointment,
            [
                'doctor_id' => 11,
                'status_changed' => true,
                'changed_fields' => ['description'],
            ]
        );

        $response = $controller->updateDescription($request, $appointment, $action, $audit);

        $this->assertSame(302, $response->getStatusCode());
    }
}
