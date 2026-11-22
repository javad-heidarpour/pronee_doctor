<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Actions\Portal\Appointment\LoadPortalAppointmentDetailsAction;
use App\Actions\Portal\Appointment\BuildPatientIndexOptionsAction;
use App\Actions\Portal\Appointment\ListPortalAppointmentsAction;
use App\Http\Requests\Portal\ListPortalAppointmentsRequest;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Portal\PatientController;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\User;
use App\Models\SurgeryType;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Inertia\Response;
use Mockery;
use Tests\TestCase;

final class PatientAppointmentClinicalPrivacyTest extends TestCase
{
    private function controller(array $clinicalPermissions = []): PatientController
    {
        $doctor = new Doctor();
        $doctor->id = 11;

        $controller = new class extends PatientController {
            public Doctor $testDoctor;

            public array $testPermissions = [];

            protected function currentDoctor(): Doctor
            {
                return $this->testDoctor;
            }

            protected function accessibleClinicIds(?Doctor $doctor = null): array
            {
                return [31];
            }

            protected function authorizeSecretaryPermission(string $permission, ?Doctor $doctor = null): void
            {
                // The test controls the permission contract through hasSecretaryPermission().
            }

            protected function hasSecretaryPermission(Doctor $doctor, string $permission): bool
            {
                return in_array($permission, $this->testPermissions, true);
            }
        };

        $controller->testDoctor = $doctor;
        $controller->testPermissions = $clinicalPermissions;

        $user = new User();
        $user->id = 99;
        $user->role = 'secretary';
        $user->is_active = true;
        Auth::guard('web')->setUser($user);

        return $controller;
    }

    private function appointment(): Appointment
    {
        $appointment = new Appointment();
        $appointment->id = 501;
        $appointment->doctor_id = 11;
        $appointment->clinic_id = 31;
        $appointment->description = 'Private diagnosis and treatment notes';

        return $appointment;
    }

    /** @return array<string, mixed> */
    private function inertiaProps(Response $response): array
    {
        $request = request();
        $request->headers->set('X-Inertia', 'true');

        return $response->toResponse($request)->getData(true)['props'];
    }

    public function test_appointments_index_redacts_visit_notes_and_surgery_details_without_clinical_access(): void
    {
        $controller = $this->controller();
        $doctor = $controller->testDoctor;
        $appointment = $this->appointment();
        $appointment->surgery_type_id = 7;
        $appointment->surgery_date = '2026-12-01';

        $surgeryType = new SurgeryType();
        $surgeryType->id = 7;
        $surgeryType->title = 'Private procedure';
        $appointment->setRelation('surgeryType', $surgeryType);

        $paginator = new LengthAwarePaginator(collect([$appointment]), 1, 20, 1);
        $request = Mockery::mock(ListPortalAppointmentsRequest::class);
        $request->shouldReceive('validated')->once()->andReturn([]);

        $action = Mockery::mock(ListPortalAppointmentsAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with($doctor, [31], [])
            ->andReturn(['appointments' => $paginator, 'stats' => []]);

        $options = Mockery::mock(BuildPatientIndexOptionsAction::class);
        $options->shouldReceive('handle')
            ->once()
            ->with($doctor, [31])
            ->andReturn([
                'clinics' => collect(),
                'visitReasons' => collect(),
                'surgeryTypes' => collect(),
            ]);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('appointments.listed', null, ['doctor_id' => 11]);

        $response = $controller->index($request, $action, $options, $audit);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($appointment->description);
        $this->assertNull($appointment->surgery_type_id);
        $this->assertNull($appointment->surgery_date);
        $this->assertNull($appointment->getRelation('surgeryType'));

        $props = $this->inertiaProps($response);
        $serializedVisit = $props['appointments']['data'][0];
        $this->assertNull($serializedVisit['description']);
        $this->assertNull($serializedVisit['surgery_date']);
        $this->assertNull($serializedVisit['surgery_type']);
    }

    public function test_secretary_without_clinical_view_permission_receives_no_visit_descriptions(): void
    {
        $controller = $this->controller();
        $appointment = $this->appointment();

        $history = new Appointment();
        $history->id = 502;
        $history->description = 'Another private visit note';
        $appointment->setRelation('patientHistory', collect([$history]));

        $action = Mockery::mock(LoadPortalAppointmentDetailsAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with($appointment, [31])
            ->andReturn($appointment);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('appointment.viewed', $appointment, ['doctor_id' => 11]);

        $response = $controller->show($appointment, $action, $audit);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNull($appointment->description);
        $this->assertNull($history->description);
        $this->assertFalse($this->inertiaProps($response)['canViewClinicalNotes']);
        $this->assertFalse($this->inertiaProps($response)['canEditVisitDetails']);
    }

    public function test_secretary_with_existing_visit_edit_permissions_can_read_the_notes_they_can_edit(): void
    {
        $controller = $this->controller(['appointments.manage', 'patients.manage']);
        $appointment = $this->appointment();

        $action = Mockery::mock(LoadPortalAppointmentDetailsAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with($appointment, [31])
            ->andReturn($appointment);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('appointment.viewed', $appointment, ['doctor_id' => 11]);

        $response = $controller->show($appointment, $action, $audit);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Private diagnosis and treatment notes', $appointment->description);
        $this->assertTrue($this->inertiaProps($response)['canViewClinicalNotes']);
        $this->assertTrue($this->inertiaProps($response)['canEditVisitDetails']);
    }

    public function test_secretary_with_clinical_view_permission_can_read_but_not_edit_visit_notes(): void
    {
        $controller = $this->controller(['patients.clinical.view']);
        $appointment = $this->appointment();

        $history = new Appointment();
        $history->id = 502;
        $history->description = 'Earlier clinical note';
        $appointment->setRelation('patientHistory', collect([$history]));

        $action = Mockery::mock(LoadPortalAppointmentDetailsAction::class);
        $action->shouldReceive('handle')
            ->once()
            ->with($appointment, [31])
            ->andReturn($appointment);

        $audit = Mockery::mock(RecordSecurityAuditEventAction::class);
        $audit->shouldReceive('handle')
            ->once()
            ->with('appointment.viewed', $appointment, ['doctor_id' => 11]);

        $response = $controller->show($appointment, $action, $audit);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('Private diagnosis and treatment notes', $appointment->description);
        $this->assertSame('Earlier clinical note', $history->description);
        $this->assertTrue($this->inertiaProps($response)['canViewClinicalNotes']);
        $this->assertFalse($this->inertiaProps($response)['canEditVisitDetails']);
    }
}
