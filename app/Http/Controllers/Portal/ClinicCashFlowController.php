<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Services\Finance\ClinicCashFlowForecastService;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ClinicCashFlowController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, ClinicCashFlowForecastService $forecast)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $user = auth('web')->user();
        abort_unless($clinicIds !== [], 403, 'هیچ شعبه مجازی برای پیش‌بینی مالی در دسترس نیست.');
        $organizations = $doctor->clinicOrganizations()->wherePivot('status', 'active');
        if ($user->role === 'doctor') {
            $organizations->where('owner_user_id', $user->id);
        }
        $organizationIds = $organizations->pluck('clinic_organizations.id')->map(fn ($id) => (int) $id)->all();
        $validated = $request->validate(['days' => ['nullable', 'integer', 'between:7,180']]);
        $days = (int) ($validated['days'] ?? 90);

        return Inertia::render('Portal/Finance/CashFlow', [
            'doctor' => $doctor,
            'forecast' => $forecast->forecast((int) $doctor->id, $clinicIds, $organizationIds, $days),
        ]);
    }
}
