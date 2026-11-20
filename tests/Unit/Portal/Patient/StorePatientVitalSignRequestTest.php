<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Http\Requests\Portal\StorePatientVitalSignRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StorePatientVitalSignRequestTest extends TestCase
{
    public function test_it_accepts_a_valid_vital_measurement(): void
    {
        $request = new StorePatientVitalSignRequest();
        $request->merge(['measurement_type' => 'heart_rate']);

        $validator = Validator::make([
            'measurement_type' => 'heart_rate',
            'value' => 78,
            'measured_at' => now()->subMinute()->toDateTimeString(),
            'note' => 'پس از استراحت',
        ], $request->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_it_rejects_unknown_measurement_types_and_values_outside_their_range(): void
    {
        $request = new StorePatientVitalSignRequest();
        $request->merge(['measurement_type' => 'oxygen_saturation']);

        $validator = Validator::make([
            'measurement_type' => 'oxygen_saturation',
            'value' => 120,
            'measured_at' => now()->subMinute()->toDateTimeString(),
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('value'));

        $request = new StorePatientVitalSignRequest();
        $request->merge(['measurement_type' => 'unsupported']);

        $validator = Validator::make([
            'measurement_type' => 'unsupported',
            'value' => 50,
            'measured_at' => now()->subMinute()->toDateTimeString(),
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('measurement_type'));
    }

    public function test_it_rejects_future_measurement_timestamps(): void
    {
        $request = new StorePatientVitalSignRequest();
        $request->merge(['measurement_type' => 'heart_rate']);

        $validator = Validator::make([
            'measurement_type' => 'heart_rate',
            'value' => 80,
            'measured_at' => now()->addHour()->toDateTimeString(),
        ], $request->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('measured_at'));
    }
}
