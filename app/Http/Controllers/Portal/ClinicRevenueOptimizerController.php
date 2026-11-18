<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Services\Finance\ClinicRevenueOptimizer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClinicRevenueOptimizerController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, ClinicRevenueOptimizer $optimizer)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);

        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'clinic_id' => ['nullable', 'integer'],
        ]);

        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->toDateString() : today()->toDateString();
        $from = isset($validated['from']) ? Carbon::parse($validated['from'])->toDateString() : today()->subDays(29)->toDateString();

        abort_if($to < $from, 422, 'تاریخ پایان باید مساوی یا بعد از تاریخ شروع باشد.');
        abort_if(Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 365, 422, 'بازه گزارش نمی‌تواند بیشتر از ۳۶۶ روز باشد.');

        $clinicId = isset($validated['clinic_id']) ? (int) $validated['clinic_id'] : null;
        if ($clinicId !== null) {
            $this->authorizeClinicAccess($clinicId, $doctor);
        }

        $clinics = Address::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('id', $clinicIds)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Portal/RevenueOptimizer/Index', [
            'doctor' => $doctor,
            'clinics' => $clinics,
            'filters' => ['from' => $from, 'to' => $to, 'clinic_id' => $clinicId],
            'report' => $optimizer->analyze($doctor->id, $clinicIds, $from, $to, $clinicId),
        ]);
    }
}
