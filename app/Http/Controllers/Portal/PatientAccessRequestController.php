<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\PatientAccessRequest;
use App\Models\PatientAccountAccess;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

final class PatientAccessRequestController extends Controller
{
    use HasActiveDoctor;

    public function index(RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);

        $requests = PatientAccessRequest::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->with(['user:id,name,family,phone,email', 'patient:id,name,phone,national_code'])
            ->latest()->paginate(30);

        $audit->handle('patient.access_requests.viewed', null, [
            'doctor_id' => $doctor->id,
        ]);

        return Inertia::render('Portal/PatientAccessRequests/Index', [
            'requests' => $requests,
            'doctor' => $doctor,
        ]);
    }

    /**
     * تأیید درخواست فقط پس از بررسی هویت توسط مرکز انجام می‌شود.
     *
     * قفل سطری مانع آن است که دو کاربر هم‌زمان یک درخواست را با وضعیت‌های
     * متفاوت تأیید/رد کنند یا درخواست در فاصله بررسی تا اعطای دسترسی تغییر کند.
     */
    public function approve(PatientAccessRequest $accessRequest, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);

        DB::transaction(function () use ($accessRequest, $doctor, $audit): void {
            // درخواست را دوباره در محدوده پزشک فعال و با قفل پایگاه داده می‌خوانیم.
            $lockedRequest = PatientAccessRequest::query()
                ->whereKey($accessRequest->id)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedRequest->status === 'pending', 409, 'این درخواست قبلاً بررسی شده است.');

            // رابطه بیمار و پزشک را در لحظه تأیید دوباره کنترل می‌کنیم؛
            // به شناسه‌های ذخیره‌شده در درخواست به‌تنهایی اعتماد نمی‌کنیم.
            $patient = Patient::query()
                ->whereKey($lockedRequest->patient_id)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            // فقط حساب کاربری عادی بیمار می‌تواند مالک دسترسی پرونده شود.
            $patientUser = User::query()
                ->whereKey($lockedRequest->user_id)
                ->where('role', 'user')
                ->firstOrFail();

            $reviewerId = Auth::guard('web')->id();

            $lockedRequest->update([
                'status' => 'approved',
                'reviewed_by_user_id' => $reviewerId,
                'reviewed_at' => now(),
            ]);

            PatientAccountAccess::query()->updateOrCreate(
                ['patient_id' => $patient->id, 'user_id' => $patientUser->id],
                [
                    'granted_by_user_id' => $reviewerId,
                    'access_type' => 'owner',
                    'permissions' => ['view_summary', 'view_records', 'view_appointments', 'view_followups', 'manage_caregivers'],
                    'expires_at' => null,
                    'revoked_at' => null,
                ]
            );

            $audit->handle('patient.access_request.approved', $lockedRequest);
        }, 3);

        return back()->with('success', 'پس از تأیید هویت توسط مرکز درمانی، دسترسی پرونده فعال شد.');
    }

    /**
     * رد درخواست نیز با قفل سطری انجام می‌شود تا وضعیت بررسی‌شده
     * توسط درخواست هم‌زمان دیگری بازنویسی نشود.
     */
    public function reject(Request $request, PatientAccessRequest $accessRequest, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $data = $request->validate(['review_note' => ['required', 'string', 'max:500']]);

        DB::transaction(function () use ($accessRequest, $doctor, $data, $audit): void {
            $lockedRequest = PatientAccessRequest::query()
                ->whereKey($accessRequest->id)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($lockedRequest->status === 'pending', 409, 'این درخواست قبلاً بررسی شده است.');

            $lockedRequest->update([
                'status' => 'rejected',
                'reviewed_by_user_id' => Auth::guard('web')->id(),
                'reviewed_at' => now(),
                'review_note' => $data['review_note'],
            ]);

            $audit->handle('patient.access_request.rejected', $lockedRequest);
        }, 3);

        return back()->with('success', 'درخواست دسترسی رد شد.');
    }
}
