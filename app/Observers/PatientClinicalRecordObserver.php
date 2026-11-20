<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\PatientClinicalRecord;

final class PatientClinicalRecordObserver
{
    private const AUDITED_FIELDS = [
        'kind',
        'title',
        'description',
        'dosage',
        'frequency',
        'start_date',
        'end_date',
        'is_active',
        'related_record_id',
        'related_allergy_record_id',
        'reaction_severity',
        'reaction_date',
        'action_taken',
        'reaction_outcome',
    ];

    public function created(PatientClinicalRecord $record): void
    {
        app(RecordSecurityAuditEventAction::class)->handle(
            'patient.clinical_record.created',
            $record,
            [
                'doctor_id' => $record->doctor_id,
                'changed_fields' => self::AUDITED_FIELDS,
            ]
        );
    }

    public function updated(PatientClinicalRecord $record): void
    {
        $changedFields = array_values(array_intersect(
            array_keys($record->getChanges()),
            self::AUDITED_FIELDS
        ));

        if ($changedFields === []) {
            return;
        }

        app(RecordSecurityAuditEventAction::class)->handle(
            'patient.clinical_record.updated',
            $record,
            [
                'doctor_id' => $record->doctor_id,
                'changed_fields' => $changedFields,
            ]
        );
    }

    public function deleted(PatientClinicalRecord $record): void
    {
        app(RecordSecurityAuditEventAction::class)->handle(
            'patient.clinical_record.archived',
            $record,
            ['doctor_id' => $record->doctor_id, 'changed_fields' => ['archived']]
        );
    }
}
