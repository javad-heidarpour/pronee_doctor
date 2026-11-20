<?php

declare(strict_types=1);

namespace Tests\Unit\Portal\Patient;

use App\Http\Requests\Portal\StorePatientEncounterRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StorePatientEncounterRequestTest extends TestCase
{
    public function test_it_accepts_a_complete_prescription_in_a_clinical_encounter(): void
    {
        $validator = Validator::make([
            'note_type' => 'consultation',
            'assessment' => 'ارزیابی بالینی',
            'plan' => 'برنامه درمان',
            'reviewed_allergies' => true,
            'reviewed_medications' => true,
            'reviewed_vitals' => true,
            'prescriptions' => [[
                'medication_name' => 'Metformin',
                'strength' => '500 mg tablet',
                'dosage' => 'یک قرص',
                'route' => 'خوراکی',
                'frequency' => 'روزی دو بار',
                'duration_days' => 30,
                'quantity' => '60 عدد',
                'instructions' => 'همراه غذا مصرف شود',
            ]],
        ], (new StorePatientEncounterRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }

    public function test_it_rejects_prescriptions_missing_required_directions(): void
    {
        $validator = Validator::make([
            'note_type' => 'consultation',
            'assessment' => 'ارزیابی بالینی',
            'plan' => 'برنامه درمان',
            'reviewed_allergies' => true,
            'reviewed_medications' => true,
            'reviewed_vitals' => true,
            'prescriptions' => [[
                'medication_name' => 'Metformin',
                'dosage' => '',
                'route' => '',
                'frequency' => '',
            ]],
        ], (new StorePatientEncounterRequest())->rules());

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('prescriptions.0.dosage'));
        $this->assertTrue($validator->errors()->has('prescriptions.0.route'));
        $this->assertTrue($validator->errors()->has('prescriptions.0.frequency'));
    }

    public function test_it_rejects_invalid_durations_and_more_than_thirty_prescription_items(): void
    {
        $base = [
            'note_type' => 'consultation',
            'assessment' => 'ارزیابی بالینی',
            'plan' => 'برنامه درمان',
            'reviewed_allergies' => true,
            'reviewed_medications' => true,
            'reviewed_vitals' => true,
        ];

        $invalidDuration = Validator::make($base + [
            'prescriptions' => [[
                'medication_name' => 'Metformin',
                'dosage' => 'یک قرص',
                'route' => 'خوراکی',
                'frequency' => 'روزانه',
                'duration_days' => 0,
            ]],
        ], (new StorePatientEncounterRequest())->rules());

        $this->assertTrue($invalidDuration->fails());
        $this->assertTrue($invalidDuration->errors()->has('prescriptions.0.duration_days'));

        $tooMany = Validator::make($base + [
            'prescriptions' => array_fill(0, 31, [
                'medication_name' => 'Medicine',
                'dosage' => 'یک عدد',
                'route' => 'خوراکی',
                'frequency' => 'روزانه',
            ]),
        ], (new StorePatientEncounterRequest())->rules());

        $this->assertTrue($tooMany->fails());
        $this->assertTrue($tooMany->errors()->has('prescriptions'));
    }

    public function test_prescriptions_are_optional_when_the_encounter_does_not_require_a_medication_order(): void
    {
        $validator = Validator::make([
            'note_type' => 'consultation',
            'assessment' => 'ارزیابی بالینی',
            'plan' => 'توصیه‌های غیردارویی',
            'reviewed_allergies' => true,
            'reviewed_medications' => true,
            'reviewed_vitals' => true,
        ], (new StorePatientEncounterRequest())->rules());

        $this->assertFalse($validator->fails(), json_encode($validator->errors()->all()));
    }
}
