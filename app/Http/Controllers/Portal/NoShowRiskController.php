<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Appointment;
use App\Services\Appointments\NoShowRiskEstimator;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class NoShowRiskController extends Controller
{
    use HasActiveDoctor;

    public function index(NoShowRiskEstimator $estimator, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403, 'هیچ مطب فعالی برای بررسی در دسترس نیست.');

        $appointments = Appointment::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->whereDate('appointment_date', '<', today())
            ->whereDate('appointment_date', '>=', today()->subYears(2))
            ->whereNotIn('status', ['cancelled', 'canceled', 'failed'])
            ->whereNotNull('patient_id')
            ->with('patient:id,name')
            ->orderBy('appointment_date')
            ->get([
                'id', 'doctor_id', 'clinic_id', 'patient_id', 'patient_name',
                'appointment_date', 'appointment_time', 'status', 'attendance_outcome',
            ]);

        $patients = $appointments->groupBy('patient_id')->map(function ($rows) use ($estimator) {
            $patient = $rows->first()->patient;

            return [
                'patient_id' => $patient?->id,
                'patient_name' => $patient?->name ?? $rows->first()->patient_name,
                ...$estimator->summarize($rows),
                'appointments' => $rows->map(fn ($appointment) => [
                    'id' => $appointment->id,
                    'date' => $appointment->appointment_date?->toDateString() ?? (string) $appointment->appointment_date,
                    'time' => $appointment->appointment_time,
                    'attendance_outcome' => $appointment->attendance_outcome,
                ])->values(),
            ];
        })->sortByDesc(fn ($row) => [
            $row['risk_band'] === 'high' ? 3 : ($row['risk_band'] === 'moderate' ? 2 : ($row['risk_band'] === 'insufficient' ? 1 : 0)),
            $row['historical_no_show_rate'] ?? -1,
        ])->values();

        $audit->handle('appointment.no_show_risk.viewed', null, [
            'doctor_id' => $doctor->id,
            'clinic_count' => count($clinicIds),
            'patient_count' => $patients->count(),
        ]);

        return Inertia::render('Portal/NoShowRisk/Index', [
            'doctor' => $doctor,
            'patients' => $patients,
            'stats' => [
                'patients' => $patients->count(),
                'insufficient_history' => $patients->where('risk_band', 'insufficient')->count(),
                'high_historical_rate' => $patients->where('risk_band', 'high')->count(),
            ],
        ]);
    }

    public function updateOutcome(Request $request, Appointment $appointment, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.manage', $doctor);
        $this->authorizeClinicAccess((int) $appointment->clinic_id, $doctor);

        abort_unless((int) $appointment->doctor_id === (int) $doctor->id, 404);
        abort_if($appointment->appointment_date >= today(), 422, 'نتیجه حضور فقط پس از تاریخ نوبت قابل ثبت است.');
        abort_if(in_array($appointment->status, ['cancelled', 'canceled', 'failed'], true), 422, 'برای نوبت لغوشده نتیجه حضور ثبت نمی‌شود.');
        abort_if($appointment->attendance_outcome !== null, 409, 'نتیجه حضور قبلاً ثبت شده است؛ اصلاح آن باید از مسیر ممیزی‌شده جداگانه انجام شود.');

        $validated = $request->validate([
            'attendance_outcome' => ['required', 'in:attended,no_show'],
        ]);

        if ($validated['attendance_outcome'] === 'attended') {
            abort_unless(in_array($appointment->status, ['visited', 'completed'], true), 422, 'برای ثبت حضور، وضعیت نوبت باید ویزیت‌شده باشد.');
        } else {
            abort_unless(!in_array($appointment->status, ['visited', 'completed'], true), 422, 'نوبت ویزیت‌شده را نمی‌توان عدم حضور ثبت کرد.');
        }

        $appointment->forceFill([
            'attendance_outcome' => $validated['attendance_outcome'],
            'attendance_outcome_recorded_at' => now(),
            'attendance_outcome_recorded_by' => $request->user('web')?->id,
        ])->save();

        $audit->handle('appointment.attendance_outcome.recorded', $appointment, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['attendance_outcome'],
        ]);

        return back()->with('success', 'نتیجه حضور ثبت شد.');
    }
}
