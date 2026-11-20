<?php

declare(strict_types=1);

namespace Tests\Unit\Portal;

use App\Http\Requests\Portal\ClinicRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class ClinicRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_coordinates_are_mapped_to_existing_address_columns(): void
    {
        $input = [
            'city_id' => 1,
            'name' => 'مطب مرکزی',
            'full_address' => 'بجنورد، خیابان اصلی',
            'phone' => '05832220000',
            'lat' => '37.4742',
            'lng' => '57.3238',
        ];

        $request = new ClinicRequest();
        $validator = Validator::make($input, array_replace($request->rules(), ['city_id' => ['required', 'integer']]));
        $request->setValidator($validator);

        $this->assertTrue($validator->passes());

        $attributes = $request->clinicAttributes();

        $this->assertSame('37.4742', $attributes['Latitude']);
        $this->assertSame('57.3238', $attributes['Longitude']);
        $this->assertArrayNotHasKey('lat', $attributes);
        $this->assertArrayNotHasKey('lng', $attributes);
    }

    public function test_invalid_coordinate_ranges_are_rejected(): void
    {
        $input = [
            'city_id' => 1,
            'name' => 'مطب مرکزی',
            'full_address' => 'بجنورد، خیابان اصلی',
            'lat' => '91',
            'lng' => '181',
        ];

        $request = new ClinicRequest();
        $validator = Validator::make($input, $request->rules());

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('lat', $validator->errors()->messages());
        $this->assertArrayHasKey('lng', $validator->errors()->messages());
    }
}
