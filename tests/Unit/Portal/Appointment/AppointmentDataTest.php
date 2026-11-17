<?php

namespace Tests\Unit\Portal\Appointment;

use App\DTOs\Portal\Appointment\CreatePortalAppointmentData;
use App\DTOs\Portal\Appointment\UpdateAppointmentDescriptionData;
use PHPUnit\Framework\TestCase;

final class AppointmentDataTest extends TestCase
{
    public function test_create_portal_appointment_data_maps_validated_fields(): void
    {
        $data = CreatePortalAppointmentData::fromValidated([
            'time_slot_id' => '19',
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
            'patient_national_code' => '0012345678',
            'description' => 'Initial consultation',
            'visit_reason_id' => '4',
            'new_visit_reason' => 'Follow-up',
        ]);

        $this->assertSame(19, $data->timeSlotId);
        $this->assertSame('Test Patient', $data->patientName);
        $this->assertSame('09120000000', $data->patientPhone);
        $this->assertSame('0012345678', $data->patientNationalCode);
        $this->assertSame('Initial consultation', $data->description);
        $this->assertSame(4, $data->visitReasonId);
        $this->assertSame('Follow-up', $data->newVisitReason);
    }

    public function test_create_portal_appointment_data_supports_optional_values(): void
    {
        $data = CreatePortalAppointmentData::fromValidated([
            'time_slot_id' => 20,
            'patient_name' => 'Test Patient',
            'patient_phone' => '09120000000',
        ]);

        $this->assertNull($data->patientNationalCode);
        $this->assertNull($data->description);
        $this->assertNull($data->visitReasonId);
        $this->assertNull($data->newVisitReason);
    }

    public function test_update_appointment_description_data_maps_optional_surgery_fields(): void
    {
        $data = UpdateAppointmentDescriptionData::fromValidated([
            'description' => 'Visit summary',
            'surgery_type_id' => '3',
            'new_surgery_type' => 'New procedure',
            'surgery_date' => '2026-12-01',
        ]);

        $this->assertSame('Visit summary', $data->description);
        $this->assertSame(3, $data->surgeryTypeId);
        $this->assertSame('New procedure', $data->newSurgeryType);
        $this->assertSame('2026-12-01', $data->surgeryDate);
    }
}
