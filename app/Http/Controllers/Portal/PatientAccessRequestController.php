<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\PatientAccessRequest;
use App\Models\PatientAccountAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

final class PatientAccessRequestController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);

        $requests = PatientAccessRequest::query()
            ->where('doctor_id', $doctor->id)
            ->where('status', 'pending')
            ->with(['user:id,name,family,phone,email', 'patient:id,name,phone,national_code'])
            ->latest()->paginate(30);

        return Inertia::render('Portal/PatientAccessRequests/Index', [
            'requests' => $requests,
            'doctor' => $doctor,
        ]);
    }

    public function approve(PatientAccessRequest $accessRequest)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        abort_unless((int) $accessRequest->doctor_id === (int) $doctor->id, 404);
        abort_unless($accessRequest->status === 'pending', 422);

        DB::transaction(function () use ($accessRequest): void {
            $accessRequest->update([
                'status' => 'approved',
                'reviewed_by_user_id' => Auth::guard('web')->id(),
                'reviewed_at' => now(),
            ]);
            PatientAccountAccess::query()->updateOrCreate(
                ['patient_id' => $accessRequest->patient_id, 'user_id' => $accessRequest->user_id],
                [
                    'granted_by_user_id' => Auth::guard('web')->id(),
                    'access_type' => 'owner',
                    'permissions' => ['view_summary', 'view_records', 'view_appointments', 'view_followups', 'manage_caregivers'],
                    'expires_at' => null,
                    'revoked_at' => null,
                ]
            );
        });

        return back()->with('success', 'پس از احراز هویت بیمار، دسترسی پرونده فعال شد.');
    }

    public function reject(Request $request, PatientAccessRequest $accessRequest)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        abort_unless((int) $accessRequest->doctor_id === (int) $doctor->id, 404);
        abort_unless($accessRequest->status === 'pending', 422);
        $data = $request->validate(['review_note' => ['required', 'string', 'max:500']]);

        $accessRequest->update([
            'status' => 'rejected',
            'reviewed_by_user_id' => Auth::guard('web')->id(),
            'reviewed_at' => now(),
            'review_note' => $data['review_note'],
        ]);

        return back()->with('success', 'درخواست دسترسی رد شد.');
    }
}
