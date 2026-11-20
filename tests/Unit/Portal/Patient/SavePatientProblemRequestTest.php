<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Http\Requests\Portal\SavePatientProblemRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class SavePatientProblemRequestTest extends TestCase
{
    public function test_it_accepts_a_valid_icd10_diagnosis(): void
    {
        $validator = Validator::make([
            'category' => 'diagnosis',
            'title' => 'Type 2 diabetes mellitus',
            'code' => 'E11.9',
            'code_system' => 'icd10',
            'description' => 'Confirmed diagnosis.',
            'status' => 'active',
            'severity' => 'moderate',
            'onset_date' => '2024-03-01',
            'source' => 'clinician_assessed',
        ], (new SavePatientProblemRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_it_rejects_invalid_categories_code_systems_and_future_onset_dates(): void
    {
        $validator = Validator::make([
            'category' => 'unknown',
            'title' => 'Invalid problem',
            'code_system' => 'unknown',
            'status' => 'active',
            'onset_date' => now()->addDay()->toDateString(),
            'source' => 'clinician_assessed',
        ], (new SavePatientProblemRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('category'));
        $this->assertTrue($validator->errors()->has('code_system'));
        $this->assertTrue($validator->errors()->has('onset_date'));
    }

    public function test_it_rejects_a_resolution_date_before_the_problem_onset(): void
    {
        $validator = Validator::make([
            'category' => 'diagnosis',
            'title' => 'Resolved condition',
            'code_system' => 'local',
            'status' => 'resolved',
            'onset_date' => '2025-05-10',
            'resolved_on' => '2025-05-09',
            'source' => 'clinician_assessed',
        ], (new SavePatientProblemRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('resolved_on'));
    }
}
