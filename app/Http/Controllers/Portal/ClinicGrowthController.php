<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Services\Growth\ClinicGrowthEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;

final class ClinicGrowthController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, ClinicGrowthEngine $engine)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('marketing.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);

        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'clinic_id' => ['nullable', 'integer'],
        ]);
        $to = isset($filters['to']) ? Carbon::parse($filters['to'])->toDateString() : today()->toDateString();
        $from = isset($filters['from']) ? Carbon::parse($filters['from'])->toDateString() : today()->subDays(29)->toDateString();

        abort_if($to < $from, 422, 'تاریخ پایان باید مساوی یا بعد از تاریخ شروع باشد.');
        abort_if(Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 365, 422, 'بازه گزارش نمی‌تواند بیشتر از ۳۶۶ روز باشد.');

        $clinicId = isset($filters['clinic_id']) ? (int) $filters['clinic_id'] : null;
        if ($clinicId !== null) {
            $this->authorizeClinicAccess($clinicId, $doctor);
        }

        $clinics = Address::query()->where('doctor_id', $doctor->id)
            ->whereIn('id', $clinicIds)->where('is_active', true)
            ->orderBy('name')->get(['id', 'name']);

        return Inertia::render('Portal/Growth/Index', [
            'doctor' => $doctor,
            'clinics' => $clinics,
            'filters' => ['from' => $from, 'to' => $to, 'clinic_id' => $clinicId],
            'report' => $engine->analyze((int) $doctor->id, $clinicIds, $from, $to, $clinicId),
        ]);
    }
}
