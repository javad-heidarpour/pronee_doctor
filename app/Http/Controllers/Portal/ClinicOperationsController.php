<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicInventoryItem;
use App\Models\ClinicInventoryMovement;
use App\Models\ClinicService;
use App\Models\ClinicStaffPayroll;
use App\Models\DoctorSettlement;
use App\Models\Patient;
use App\Models\PatientDentalChartEntry;
use App\Models\PatientFollowUpTask;
use App\Models\PatientTreatmentItem;
use App\Models\PatientTreatmentPlan;
use App\Services\Finance\ClinicAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClinicOperationsController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $clinicIds = $this->accessibleClinicIds($doctor);
        $this->authorizeSecretaryPermission('services.view', $doctor);
        return Inertia::render('Portal/Operations/Index', [
            'doctor' => $doctor,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->get(['id', 'name', 'organization_id']),
            'patients' => Patient::query()->where('doctor_id', $doctor->id)->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))->orderBy('name')->get(['id', 'name', 'phone']),
            'organizations' => $doctor->clinicOrganizations()->wherePivot('status', 'active')->get(['clinic_organizations.id', 'clinic_organizations.name']),
            'settlements' => DoctorSettlement::query()->where('doctor_id', $doctor->id)->latest()->limit(12)->get(),
            'services' => ClinicService::query()->whereIn('clinic_id', $clinicIds)->where('is_active', true)->orderBy('name')->get(),
            'plans' => PatientTreatmentPlan::query()->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds)
                ->with(['patient:id,name,phone', 'items'])->latest()->limit(30)->get(),
            'inventory' => ClinicInventoryItem::query()->whereIn('clinic_id', $clinicIds)->where('is_active', true)->with('clinic:id,name')->orderBy('name')->get(),
            'lowStockCount' => ClinicInventoryItem::query()->whereIn('clinic_id', $clinicIds)->where('is_active', true)->lowStock()->count(),
            'dentalChart' => PatientDentalChartEntry::query()->where('doctor_id', $doctor->id)->latest('recorded_at')->limit(60)->with('patient:id,name')->get(),
            'canManage' => Auth::guard('web')->user()?->role !== 'secretary' || $this->secretaryHas($doctor, 'services.manage'),
            'canManageInventory' => Auth::guard('web')->user()?->role !== 'secretary' || $this->secretaryHas($doctor, 'inventory.manage'),
            'canManageFinance' => Auth::guard('web')->user()?->role !== 'secretary' || $this->secretaryHas($doctor, 'finance.manage'),
        ]);
    }

    public function storeService(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('services.manage', $doctor);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:180'],
            'code' => ['nullable', 'string', 'max:60'], 'specialty_module' => ['required', Rule::in(['general','dentistry','dermatology','physiotherapy','surgery','other'])],
            'default_duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'default_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'doctor_share_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'requires_follow_up' => ['boolean'], 'follow_up_after_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $organizationId = Address::whereKey($clinicId)->value('organization_id');
        ClinicService::create($data + ['organization_id' => $organizationId]);
        return back()->with('success', 'خدمت به فهرست خدمات شعبه اضافه شد.');
    }

    public function storeTreatmentItem(Request $request, int $plan, ClinicAccountingService $accounting)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $treatmentPlan = PatientTreatmentPlan::query()->whereKey($plan)->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))->firstOrFail();
        $data = $request->validate([
            'service_id' => ['nullable', 'integer'], 'title' => ['required', 'string', 'max:180'],
            'item_type' => ['required', Rule::in(['procedure','consultation','lab','imaging','medication','other'])],
            'tooth_number' => ['nullable', 'string', 'max:8'], 'tooth_surface' => ['nullable', Rule::in(['M','D','O','I','B','L','F','P'])],
            'sequence' => ['required', 'integer', 'min:1', 'max:500'],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'doctor_share_basis_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'follow_up_after_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'clinical_notes' => ['nullable', 'string', 'max:5000'],
            
        ]);
        $service = null;
        if (!empty($data['service_id'])) {
            $service = ClinicService::query()->whereKey($data['service_id'])
                ->where('clinic_id', $treatmentPlan->clinic_id)->firstOrFail();
        }
        $item = $treatmentPlan->items()->create([
            ...$data,
            'provider_doctor_id' => $doctor->id,
            'doctor_share_basis_points' => $data['doctor_share_basis_points'] ?? $service?->doctor_share_basis_points,
            'follow_up_after_days' => $data['follow_up_after_days'] ?? $service?->follow_up_after_days,
            'stage' => 'planned',
        ]);
        return back()->with('success', 'مرحله درمان ثبت شد.');
    }

    public function updateTreatmentItem(Request $request, int $item, ClinicAccountingService $accounting)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $row = PatientTreatmentItem::query()->whereKey($item)
            ->whereHas('plan', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $this->accessibleClinicIds($doctor)))
            ->with('plan.patient')->firstOrFail();
        abort_if($row->stage === 'completed' || $row->stage === 'cancelled', 422, 'مرحله نهایی‌شده قابل تغییر نیست.');
        $data = $request->validate([
            'stage' => ['required', Rule::in(['planned','scheduled','in_progress','completed','cancelled'])],
            'appointment_id' => ['nullable', 'integer'], 'clinical_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        if (!empty($data['appointment_id'])) {
            $appointment = $row->plan->doctor->appointments()->whereKey($data['appointment_id'])
                ->where('clinic_id', $row->plan->clinic_id)->firstOrFail();
            $row->appointment_id = $appointment->id;
        }
        $row->stage = $data['stage'];
        $row->clinical_notes = $data['clinical_notes'] ?? $row->clinical_notes;
        if ($data['stage'] === 'completed') {
            $row->performed_at = now();
            $days = $row->follow_up_after_days;
            if ($days) $row->follow_up_due_at = now()->addDays($days);
        }
        $row->save();

        if ($data['stage'] === 'completed') {
            $clinicId = $row->plan->clinic_id;
            $orgId = $row->plan->organization_id;
            $accounting->recordTreatmentRevenue(
                $orgId, $clinicId, $row->provider_doctor_id ?: $doctor->id,
                (int) Auth::guard('web')->id(), $row->id, (int) $row->price,
                $row->title, $row->performed_at->toDateTimeString()
            );
            if ($row->follow_up_due_at) {
                PatientFollowUpTask::create([
                    'doctor_id' => $row->plan->doctor_id, 'clinic_id' => $clinicId,
                    'patient_id' => $row->plan->patient_id, 'appointment_id' => $row->appointment_id,
                    'created_by' => Auth::guard('web')->id(), 'title' => 'پیگیری پس از درمان: ' . $row->title,
                    'description' => 'پیگیری دوره‌ای ایجادشده از مرحله درمان؛ نتیجه و نیاز به مراجعه مجدد ثبت شود.',
                    'due_at' => $row->follow_up_due_at, 'priority' => 'normal',
                    'contact_method' => 'call', 'send_sms' => false, 'sms_consent_confirmed' => false,
                    'status' => 'pending',
                ]);
            }
        }
        return back()->with('success', 'وضعیت مرحله درمان به‌روزرسانی شد.');
    }

    public function storeDentalChart(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $data = $request->validate([
            'patient_id' => ['required', 'integer'], 'clinic_id' => ['required', 'integer'],
            'dentition' => ['required', Rule::in(['permanent','primary'])],
            'tooth_number' => ['required', 'string', 'max:8'],
            'surface' => ['nullable', Rule::in(['M','D','O','I','B','L','F','P'])],
            'condition' => ['required', Rule::in(['healthy','caries','filled','crown','missing','root_canal','implant','fracture','watch'])],
            'severity' => ['nullable', Rule::in(['low','medium','high'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $patient = Patient::query()->whereKey($data['patient_id'])->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->where('clinic_id', $clinicId))->firstOrFail();
        PatientDentalChartEntry::create($data + [
            'doctor_id' => $doctor->id, 'recorded_by' => Auth::guard('web')->id(), 'recorded_at' => now(),
        ]);
        return back()->with('success', 'وضعیت دندان در نمودار بیمار ثبت شد.');
    }

    public function storeInventoryItem(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('inventory.manage', $doctor);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:80'], 'unit' => ['required', 'string', 'max:24'],
            'minimum_quantity' => ['required', 'numeric', 'min:0'], 'opening_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $organizationId = Address::whereKey($clinicId)->value('organization_id');
        DB::transaction(function () use ($data, $clinicId, $organizationId): void {
            $item = ClinicInventoryItem::create([
                'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'name' => $data['name'],
                'sku' => $data['sku'] ?? null, 'unit' => $data['unit'], 'minimum_quantity' => $data['minimum_quantity'],
                'current_quantity' => $data['opening_quantity'] ?? 0, 'average_unit_cost' => $data['unit_cost'] ?? 0,
            ]);
            if (($data['opening_quantity'] ?? 0) > 0) {
                $item->movements()->create([
                    'recorded_by' => Auth::guard('web')->id(), 'movement_type' => 'opening',
                    'quantity' => $data['opening_quantity'], 'unit_cost' => $data['unit_cost'] ?? 0,
                    'occurred_at' => now(), 'notes' => 'موجودی افتتاحیه',
                ]);
            }
        });
        return back()->with('success', 'کالای انبار ثبت شد.');
    }

    public function moveStock(Request $request, int $item, ClinicAccountingService $accounting)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('inventory.manage', $doctor);
        $data = $request->validate([
            'movement_type' => ['required', Rule::in(['purchase','use','return','adjustment'])],
            'quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
            'unit_cost' => ['nullable', 'integer', 'min:0'], 'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        DB::transaction(function () use ($doctor, $item, $data, $accounting): void {
            $row = ClinicInventoryItem::query()->whereKey($item)
                ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
                ->lockForUpdate()->firstOrFail();
            $delta = match ($data['movement_type']) {
                'purchase', 'return', 'adjustment' => (float) $data['quantity'],
                'use' => -(float) $data['quantity'],
            };
            $newQuantity = round((float) $row->current_quantity + $delta, 3);
            abort_if($newQuantity < 0, 422, 'موجودی انبار برای این خروج کافی نیست.');
            if ($data['movement_type'] === 'purchase' && $newQuantity > 0) {
                $oldValue = (float) $row->current_quantity * (int) $row->average_unit_cost;
                $newValue = (float) $data['quantity'] * (int) ($data['unit_cost'] ?? 0);
                $row->average_unit_cost = (int) round(($oldValue + $newValue) / $newQuantity);
            }
            $row->current_quantity = $newQuantity;
            $row->save();
            $movement = $row->movements()->create([
                'recorded_by' => Auth::guard('web')->id(), 'movement_type' => $data['movement_type'],
                'quantity' => $data['quantity'], 'unit_cost' => $data['unit_cost'] ?? $row->average_unit_cost,
                'reference' => $data['reference'] ?? null, 'notes' => $data['notes'] ?? null, 'occurred_at' => now(),
            ]);
            $cost = (int) round((float) $data['quantity'] * (int) ($data['unit_cost'] ?? $row->average_unit_cost));
            $accounting->recordInventoryMovement(
                $row->organization_id, $row->clinic_id, $doctor->id, (int) Auth::guard('web')->id(),
                $movement->id, $data['movement_type'], $cost, $row->name, now()->toDateTimeString()
            );
        }, 3);
        return back()->with('success', 'گردش انبار ثبت شد.');
    }

    public function createSettlement(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:clinic_organizations,id'],
            'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);
        $organization = $doctor->clinicOrganizations()->whereKey($data['organization_id'])->wherePivot('status', 'active')->firstOrFail();
        abort_unless($organization->owner_user_id === Auth::guard('web')->id(), 403);
        $share = (int) ($organization->pivot->revenue_share_basis_points ?? 7000);
        $items = PatientTreatmentItem::query()->where('provider_doctor_id', $doctor->id)->where('stage', 'completed')
            ->whereBetween('performed_at', [$data['period_start'] . ' 00:00:00', $data['period_end'] . ' 23:59:59'])
            ->whereHas('plan', fn ($q) => $q->where('organization_id', $organization->id))->get();
        $gross = (int) $items->sum('price');
        $doctorShare = (int) $items->sum(fn ($item) => (int) round($item->price * (($item->doctor_share_basis_points ?? $share) / 10000)));
        $settlement = DoctorSettlement::firstOrCreate([
            'organization_id' => $organization->id, 'doctor_id' => $doctor->id,
            'period_start' => $data['period_start'], 'period_end' => $data['period_end'],
        ], [
            'gross_amount' => $gross, 'clinic_share_amount' => $gross - $doctorShare,
            'adjustment_amount' => 0, 'net_amount' => $doctorShare, 'status' => 'draft',
        ]);
        return back()->with('success', 'پیش‌نویس تسویه پزشک ساخته شد.');
    }

    public function paySettlement(int $settlement, ClinicAccountingService $accounting)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $row = DoctorSettlement::query()->whereKey($settlement)->where('doctor_id', $doctor->id)
            ->where('status', 'approved')->firstOrFail();
        DB::transaction(function () use ($row, $accounting): void {
            $row->update(['status' => 'paid', 'paid_by' => Auth::guard('web')->id(), 'paid_at' => now()]);
            $accounting->post(
                $row->organization_id, null, $row->doctor_id, (int) Auth::guard('web')->id(),
                'doctor_settlement', $row->id, 'تسویه سهم پزشک', now()->toDateTimeString(),
                'doctor-settlement:' . $row->id,
                [['code' => '5100', 'debit' => (int) $row->net_amount, 'credit' => 0],
                 ['code' => '1000', 'debit' => 0, 'credit' => (int) $row->net_amount]]
            );
        });
        return back()->with('success', 'تسویه پزشک پرداخت و ثبت حسابداری شد.');
    }

    public function approveSettlement(int $settlement)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $row = DoctorSettlement::query()->whereKey($settlement)->where('doctor_id', $doctor->id)->where('status', 'draft')->firstOrFail();
        $row->update(['status' => 'approved', 'approved_by' => Auth::guard('web')->id()]);
        return back()->with('success', 'تسویه تأیید شد.');
    }

    private function secretaryHas($doctor, string $permission): bool
    {
        $user = Auth::guard('web')->user();
        $assignment = $user?->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) $permissions = json_decode($permissions, true) ?: [];
        return is_array($permissions) && in_array($permission, $permissions, true);
    }
}
