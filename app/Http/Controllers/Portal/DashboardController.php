<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Dashboard\BuildDashboardAction;
use App\Actions\Portal\Dashboard\BuildDoctorSelectionOptionsAction;
use App\Actions\Portal\Dashboard\ResolveActiveDoctorAction;
use App\Actions\Portal\Dashboard\SelectActiveDoctorAction;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function selectDoctor(int $doctor, SelectActiveDoctorAction $action)
    {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401);

        $action->handle($user, $doctor);

        return redirect()->route('portal.dashboard');
    }

    public function index(
        ResolveActiveDoctorAction $resolveDoctor,
        BuildDashboardAction $buildDashboard,
        BuildDoctorSelectionOptionsAction $selectionOptions
    ) {
        $user = Auth::guard('web')->user();

        abort_unless($user, 401);

        ['doctors' => $doctors, 'activeDoctorId' => $activeDoctorId] =
            $resolveDoctor->handle($user);

        // اگر چند پزشک وجود دارد و هنوز انتخاب نشده
        if (!$activeDoctorId) {
            return Inertia::render('Portal/SelectDoctor', [
                'doctors' => $selectionOptions->handle($doctors),
            ]);
        }

        return Inertia::render(
            'Portal/Dashboard',
            $buildDashboard->handle($user, $doctors, $activeDoctorId)
        );
    }
}
