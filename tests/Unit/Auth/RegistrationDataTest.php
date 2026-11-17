<?php

namespace Tests\Unit\Auth;

use App\DTOs\Auth\RegisterDoctorData;
use App\DTOs\Auth\RegisterPublicUserData;
use PHPUnit\Framework\TestCase;

class RegistrationDataTest extends TestCase
{
    public function test_public_registration_data_normalizes_email(): void
    {
        $data = RegisterPublicUserData::fromValidated([
            'name' => 'Test User',
            'family' => 'Tester',
            'email' => 'TEST@EXAMPLE.COM',
            'password' => 'secret-password',
        ]);

        $this->assertSame('Test User', $data->name);
        $this->assertSame('Tester', $data->family);
        $this->assertSame('test@example.com', $data->email);
        $this->assertSame('secret-password', $data->password);
    }

    public function test_doctor_registration_data_maps_validated_fields_to_typed_properties(): void
    {
        $data = RegisterDoctorData::fromValidated([
            'name' => 'Javad',
            'family' => 'Test',
            'city_id' => '2',
            'specialty_id' => '4',
            'slug' => 'javad-test',
            'email' => 'doctor@example.com',
            'medical_system_number' => '001234',
        ], '09120000000');

        $this->assertSame(2, $data->cityId);
        $this->assertSame(4, $data->specialtyId);
        $this->assertSame('09120000000', $data->phone);
        $this->assertSame('001234', $data->medicalSystemNumber);
    }
}
