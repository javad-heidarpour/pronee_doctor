<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Services\ClinicBrain\ClinicBrainAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

final class ClinicBrainController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, ClinicBrainAnalyzer $analyzer)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);

        $doctor = $this->currentDoctor();
        $clinicIds = $this->accessibleClinicIds($doctor);

        if ($user->role === 'secretary') {
            $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
            $permissions = $assignment?->pivot?->permissions ?? [];
            if (is_string($permissions)) {
                $permissions = json_decode($permissions, true) ?: [];
            }
            abort_unless(is_array($permissions) && (
                in_array('appointments.view', $permissions, true)
                || in_array('appointments.manage', $permissions, true)
                || in_array('shifts.view', $permissions, true)
            ), 403, 'مجوز مشاهده تحلیل عملیاتی برای شما فعال نیست.');
        }

        $selectedClinicId = $request->integer('clinic_id') ?: null;
        if ($selectedClinicId !== null) {
            abort_unless(in_array($selectedClinicId, $clinicIds, true), 403);
        }

        return Inertia::render('Portal/ClinicBrain/Index', [
            'doctor' => [
                'id' => $doctor->id, 'name' => $doctor->name,
                'family' => $doctor->family, 'caption' => $doctor->caption,
            ],
            'clinics' => Address::query()->whereIn('id', $clinicIds)->orderBy('name')->get(['id', 'name']),
            'selectedClinicId' => $selectedClinicId,
            'brain' => $analyzer->analyze($doctor->id, $clinicIds, $selectedClinicId),
        ]);
    }
}
