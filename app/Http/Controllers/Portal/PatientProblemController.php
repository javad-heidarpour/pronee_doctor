<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Patient\SavePatientProblemAction;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\SavePatientProblemRequest;
use App\Models\Patient;
use App\Models\PatientProblem;
use Illuminate\Support\Facades\Auth;

final class PatientProblemController extends Controller
{
    use HasActiveDoctor;

    public function store(
        SavePatientProblemRequest $request,
        Patient $patient,
        SavePatientProblemAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $patient = $this->scopedPatient($patient, $doctor, $clinicIds);

        $problem = $action->handle($patient, Auth::guard('web')->user(), $request->validated(), null, $clinicIds);

        $audit->handle('patient.problem.created', $problem, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['category', 'title', 'code', 'status', 'severity'],
        ]);

        return back()->with('success', 'تشخیص یا مشکل بیمار ثبت شد.');
    }

    public function update(
        SavePatientProblemRequest $request,
        Patient $patient,
        PatientProblem $problem,
        SavePatientProblemAction $action,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $patient = $this->scopedPatient($patient, $doctor, $clinicIds);

        abort_unless(
            (int) $problem->patient_id === (int) $patient->id &&
            (int) $problem->doctor_id === (int) $doctor->id,
            404
        );

        $updated = $action->handle($patient, Auth::guard('web')->user(), $request->validated(), $problem, $clinicIds);

        $audit->handle('patient.problem.updated', $updated, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['category', 'title', 'code', 'status', 'severity'],
        ]);

        return back()->with('success', 'تشخیص یا مشکل بیمار به‌روزرسانی شد.');
    }

    private function scopedPatient(Patient $patient, $doctor, array $clinicIds): Patient
    {
        abort_if($clinicIds === [], 403, 'هیچ مطب مجازی برای این حساب در دسترس نیست.');

        return Patient::query()
            ->whereKey($patient->id)
            ->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($query) => $query->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds))
            ->firstOrFail();
    }
}