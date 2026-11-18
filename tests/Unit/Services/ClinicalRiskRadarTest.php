<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Patient;
use App\Services\ClinicalRiskRadar;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class ClinicalRiskRadarTest extends TestCase
{
    public function test_extreme_clinic_measurement_is_flagged_for_urgent_review(): void
    {
        $patient = new Patient();
        $patient->setAttribute('id', 12);
        $patient->setAttribute('name', 'بیمار آزمایشی');
        $patient->setRelation('vitalSigns', collect([
            (object) ['measurement_type' => 'systolic_bp', 'value' => 185, 'unit' => 'mmHg', 'measured_at' => now()->subMinutes(5)],
        ]));

        $result = app(ClinicalRiskRadar::class)->evaluate($patient, new Collection(), new Collection(), new Collection());

        $this->assertSame('urgent', $result['priority']);
        $this->assertSame('clinic', $result['flags'][0]['source']);
    }

    public function test_patient_entered_observation_never_creates_urgent_flag(): void
    {
        $patient = new Patient();
        $patient->setAttribute('id', 13);
        $patient->setAttribute('name', 'بیمار آزمایشی');
        $patient->setRelation('vitalSigns', new Collection());

        $observations = collect([
            (object) ['measurement_type' => 'systolic_bp', 'value' => 190, 'unit' => 'mmHg', 'observed_at' => now()->subMinutes(2)],
        ]);

        $result = app(ClinicalRiskRadar::class)->evaluate($patient, new Collection(), $observations, new Collection());

        $this->assertSame('review', $result['priority']);
        $this->assertSame('patient', $result['flags'][0]['source']);
        $this->assertSame('review', $result['flags'][0]['level']);
    }

    public function test_repeated_explicit_skipped_doses_create_review_flag(): void
    {
        $patient = new Patient();
        $patient->setAttribute('id', 14);
        $patient->setAttribute('name', 'بیمار آزمایشی');
        $patient->setRelation('vitalSigns', new Collection());

        $doses = collect([1, 2, 3]->map(fn ($id) => (object) [
            'id' => $id,
            'status' => 'skipped',
            'scheduled_for' => now()->subDays($id),
        ]));
        $plans = collect([(object) ['doses' => $doses]]);

        $result = app(ClinicalRiskRadar::class)->evaluate($patient, new Collection(), new Collection(), $plans);

        $this->assertSame('review', $result['priority']);
        $this->assertTrue(collect($result['flags'])->contains(fn ($flag) => $flag['source'] === 'medication'));
    }
    public function test_missing_recent_data_is_not_classified_as_routine(): void
    {
        $patient = new Patient();
        $patient->setAttribute('id', 15);
        $patient->setAttribute('name', 'بیمار بدون داده');
        $patient->setRelation('vitalSigns', new Collection());

        $result = app(ClinicalRiskRadar::class)->evaluate($patient, new Collection(), new Collection(), new Collection());

        $this->assertSame('unknown', $result['priority']);
        $this->assertSame('داده کافی نیست', $result['priority_label']);
    }

}
