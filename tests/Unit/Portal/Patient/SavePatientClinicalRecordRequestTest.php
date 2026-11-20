<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Http\Requests\Portal\SavePatientClinicalRecordRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class SavePatientClinicalRecordRequestTest extends TestCase
{
    public function test_it_accepts_a_structured_patient_allergy_record(): void
    {
        $validator = Validator::make([
            'kind' => 'allergy',
            'title' => 'حساسیت به پنی‌سیلین',
            'description' => 'واکنش پوستی طبق اظهار بیمار',
            'start_date' => '2020-03-10',
            'end_date' => null,
            'is_active' => true,
        ], (new SavePatientClinicalRecordRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_it_rejects_unknown_record_kinds_and_missing_allergy_titles(): void
    {
        $validator = Validator::make([
            'kind' => 'unknown',
            'title' => '',
            'is_active' => true,
        ], (new SavePatientClinicalRecordRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('kind'));
        $this->assertTrue($validator->errors()->has('title'));
    }

    public function test_it_rejects_an_end_date_before_the_allergy_start_date(): void
    {
        $validator = Validator::make([
            'kind' => 'allergy',
            'title' => 'حساسیت دارویی',
            'start_date' => '2024-06-10',
            'end_date' => '2024-06-09',
            'is_active' => true,
        ], (new SavePatientClinicalRecordRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('end_date'));
    }

    public function test_reaction_only_fields_are_excluded_for_an_allergy_record(): void
    {
        $validator = Validator::make([
            'kind' => 'allergy',
            'title' => 'حساسیت غذایی',
            'reaction_severity' => 'not-a-valid-severity',
            'reaction_date' => 'not-a-date',
            'reaction_outcome' => 'unknown-value',
        ], (new SavePatientClinicalRecordRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
        $validated = $validator->validated();
        $this->assertArrayNotHasKey('reaction_severity', $validated);
        $this->assertArrayNotHasKey('reaction_date', $validated);
        $this->assertArrayNotHasKey('reaction_outcome', $validated);
    }
}
