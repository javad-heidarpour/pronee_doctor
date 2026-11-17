<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Patient;

final class PatientAuditObserver
{
    private const AUDITED_FIELDS = [
        'name',
        'phone',
        'national_code',
    ];

    public function created(Patient $patient): void
    {
        app(RecordSecurityAuditEventAction::class)->handle(
            'patient.profile.created',
            $patient,
            [
                'doctor_id' => $patient->doctor_id,
                'changed_fields' => self::AUDITED_FIELDS,
            ]
        );
    }

    public function updated(Patient $patient): void
    {
        $changedFields = array_values(array_intersect(
            array_keys($patient->getChanges()),
            self::AUDITED_FIELDS
        ));

        if ($changedFields === []) {
            return;
        }

        app(RecordSecurityAuditEventAction::class)->handle(
            'patient.profile.updated',
            $patient,
            [
                'doctor_id' => $patient->doctor_id,
                'changed_fields' => $changedFields,
            ]
        );
    }
}
