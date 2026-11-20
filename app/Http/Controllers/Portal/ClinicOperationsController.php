<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\Address;
use App\Models\ClinicInventoryItem;
use App\Models\ClinicInventoryMovement;
use App\Models\ClinicOrganization;
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

    public function index(RecordSecurityAuditEventAction $audit)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $clinicIds = $this->accessibleOperationsClinicIds($doctor);
        $user = Auth::guard('web')->user();
        abort_unless(in_array($user->role, ['doctor', 'secretary'], true), 403);
        $isPrivileged = $user->role === 'doctor';
        $hasAnyModulePermission = $isPrivileged || collect(['services.view','services.manage','inventory.view','inventory.manage','patients.clinical.view','patients.clinical.manage'])->contains(fn ($permission) => $this->secretaryHas($doctor, $permission));
        abort_unless($hasAnyModulePermission, 403, 'مجوز مشاهده عملیات کلینیک برای شما فعال نیست.');
        $organizations = $doctor->clinicOrganizations()->wherePivot('status', 'active')
            ->with(['doctors' => fn ($q) => $q->where('clinic_organization_doctors.status', 'active')])
            ->get(['clinic_organizations.id', 'clinic_organizations.name', 'clinic_organizations.owner_user_id']);
        $ownedOrganizationIds = $organizations->where('owner_user_id', $user->id)->pluck('id')->values();
        $payrollOrganizations = $organizations->whereIn('id', $ownedOrganizationIds)->values();
        $payrollOrganizations->load(['members' => fn ($q) => $q->where('clinic_organization_members.is_active', true), 'clinics']);
        $canViewServices = $isPrivileged || $this->secretaryHas($doctor, 'services.view') || $this->secretaryHas($doctor, 'services.manage');
        $canViewClinical = $isPrivileged || $this->secretaryHas($doctor, 'patients.clinical.view') || $this->secretaryHas($doctor, 'patients.clinical.manage');
        $canManageClinical = $isPrivileged || $this->secretaryHas($doctor, 'patients.clinical.manage');
        $canViewInventory = $isPrivileged || $this->secretaryHas($doctor, 'inventory.view') || $this->secretaryHas($doctor, 'inventory.manage');
        $canManageFinance = $isPrivileged && $ownedOrganizationIds->isNotEmpty();

        $audit->handle('clinic.operations.viewed', null, [
            'doctor_id' => $doctor->id,
            'clinic_count' => count($clinicIds),
        ]);

        return Inertia::render('Portal/Operations/Index', [
            'doctor' => $doctor,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->get(['id', 'name', 'organization_id']),
            'patients' => $canViewClinical ? Patient::query()->where('doctor_id', $doctor->id)->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))->orderBy('name')->get(['id', 'name', 'phone']) : collect(),
            'organizations' => $organizations,
            'payrollOrganizations' => $payrollOrganizations,
            'settlements' => DoctorSettlement::query()->whereIn('organization_id', $ownedOrganizationIds)->with('doctor:id,name,family')->latest()->limit(20)->get(),
            'payrolls' => ClinicStaffPayroll::query()->whereIn('organization_id', $ownedOrganizationIds)->with('staff:id,name,family')->latest()->limit(20)->get(),
            'services' => $canViewServices ? ClinicService::query()->whereIn('clinic_id', $clinicIds)->where('is_active', true)->orderBy('name')->get() : collect(),
            'plans' => $canViewClinical
                ? PatientTreatmentPlan::query()->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds)
                    ->with(['patient:id,name,phone', 'items'])->latest()->limit(30)->get()
                : collect(),
            'inventory' => $canViewInventory ? ClinicInventoryItem::query()->whereIn('clinic_id', $clinicIds)->where('is_active', true)->with('clinic:id,name')->orderBy('name')->get() : collect(),
            'lowStockCount' => $canViewInventory ? ClinicInventoryItem::query()->whereIn('clinic_id', $clinicIds)->where('is_active', true)->lowStock()->count() : 0,
            'dentalChart' => $canViewClinical ? PatientDentalChartEntry::query()->where('doctor_id', $doctor->id)->latest('recorded_at')->limit(60)->with('patient:id,name')->get() : collect(),
            'canManage' => $isPrivileged || $this->secretaryHas($doctor, 'services.manage'),
            'canViewServices' => $canViewServices,
            'canViewClinical' => $canViewClinical,
            'canManageClinical' => $canManageClinical,
            'canViewInventory' => $canViewInventory,
            'canManageInventory' => Auth::guard('web')->user()?->role !== 'secretary' || $this->secretaryHas($doctor, 'inventory.manage'),
            'canManageFinance' => $canManageFinance,
        ]);
    }

    public function storeService(Request $request)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('services.manage', $doctor);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:180'],
            'code' => ['nullable', 'string', 'max:60'], 'specialty_module' => ['required', Rule::in(['general','dentistry','dermatology','physiotherapy','surgery','other'])],
            'default_duration_minutes' => ['required', 'integer', 'min:5', 'max:600'],
            'default_price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'doctor_share_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'requires_follow_up' => ['boolean'], 'follow_up_after_days' => ['nullable', 'required_if:requires_follow_up,1', 'integer', 'min:1', 'max:3650'],
            'inventory_requirements' => ['nullable', 'array', 'max:30'],
            'inventory_requirements.*.inventory_item_id' => ['required', 'integer', 'distinct'],
            'inventory_requirements.*.quantity' => ['required', 'numeric', 'gt:0', 'max:999999'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeOperationsClinicAccess($clinicId, $doctor);
        $organizationId = Address::whereKey($clinicId)->value('organization_id');
        DB::transaction(function () use ($data, $clinicId, $organizationId): void {
            $service = ClinicService::create(collect($data)->except('inventory_requirements')->all() + ['organization_id' => $organizationId]);
            $requirements = $data['inventory_requirements'] ?? [];
            $itemIds = collect($requirements)->pluck('inventory_item_id')->map(fn ($id) => (int) $id)->unique();
            $validIds = ClinicInventoryItem::query()->where('clinic_id', $clinicId)->whereIn('id', $itemIds)->pluck('id')->map(fn ($id) => (int) $id);
            abort_unless($validIds->count() === $itemIds->count(), 422, 'مواد مصرفی باید از انبار همین شعبه انتخاب شوند.');
            foreach ($requirements as $requirement) {
                $service->inventoryRequirements()->create([
                    'inventory_item_id' => $requirement['inventory_item_id'],
                    'quantity' => $requirement['quantity'],
                ]);
            }
        }, 3);
        return back()->with('success', 'خدمت، تعرفه و مواد مصرفی استاندارد آن ثبت شد.');
    }

    public function storeTreatmentItem(
        Request $request,
        int $plan,
        ClinicAccountingService $accounting,
        RecordSecurityAuditEventAction $audit
    ) {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $data = $request->validate([
            'service_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'item_type' => ['required', Rule::in(['procedure', 'consultation', 'lab', 'imaging', 'medication', 'other'])],
            'tooth_number' => ['nullable', Rule::in(['11','12','13','14','15','16','17','18','21','22','23','24','25','26','27','28','31','32','33','34','35','36','37','38','41','42','43','44','45','46','47','48','51','52','53','54','55','61','62','63','64','65','71','72','73','74','75','81','82','83','84','85'])],
            'tooth_surface' => ['nullable', Rule::in(['M', 'D', 'O', 'I', 'B', 'L', 'F', 'P'])],
            'sequence' => ['required', 'integer', 'min:1', 'max:500', Rule::unique('patient_treatment_items', 'sequence')->where(fn ($query) => $query->where('treatment_plan_id', $plan))],
            'price' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'doctor_share_basis_points' => ['nullable', 'integer', 'min:0', 'max:10000'],
            'follow_up_after_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'clinical_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $item = DB::transaction(function () use ($plan, $doctor, $data): PatientTreatmentItem {
            $treatmentPlan = PatientTreatmentPlan::query()
                ->whereKey($plan)
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $this->accessibleOperationsClinicIds($doctor))
                ->lockForUpdate()
                ->firstOrFail();

            $service = null;
            if (!empty($data['service_id'])) {
                $service = ClinicService::query()
                    ->whereKey($data['service_id'])
                    ->where('clinic_id', $treatmentPlan->clinic_id)
                    ->firstOrFail();
            }

            $alreadyPlanned = (int) $treatmentPlan->items()->sum('price');
            abort_if(
                $alreadyPlanned + (int) $data['price'] > $treatmentPlan->net_amount,
                422,
                'جمع هزینه مراحل درمان نمی‌تواند از مبلغ خالص طرح درمان بیشتر باشد.'
            );

            return $treatmentPlan->items()->create([
                ...$data,
                'provider_doctor_id' => $doctor->id,
                'doctor_share_basis_points' => $data['doctor_share_basis_points'] ?? $service?->doctor_share_basis_points,
                'follow_up_after_days' => $data['follow_up_after_days'] ?? $service?->follow_up_after_days,
                'stage' => 'planned',
            ]);
        }, 3);

        $audit->handle('patient.treatment_item.created', $item, [
            'doctor_id' => $doctor->id,
            'treatment_plan_id' => $plan,
            'clinic_id' => $item->plan?->clinic_id,
        ]);

        return back()->with('success', 'مرحله درمان ثبت شد.');
    }

    public function updateTreatmentItem(Request $request, int $item, ClinicAccountingService $accounting, RecordSecurityAuditEventAction $audit)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $data = $request->validate([
            'stage' => ['required', Rule::in(['planned','scheduled','in_progress','completed','cancelled'])],
            'appointment_id' => ['nullable', 'integer'], 'clinical_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $updatedItem = DB::transaction(function () use ($doctor, $item, $data, $accounting): PatientTreatmentItem {
            $row = PatientTreatmentItem::query()->whereKey($item)
                ->whereHas('plan', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $this->accessibleOperationsClinicIds($doctor)))
                ->with('plan.patient')->lockForUpdate()->firstOrFail();
            abort_if(in_array($row->stage, ['completed', 'cancelled'], true), 422, 'مرحله نهایی‌شده قابل تغییر نیست.');

            if (!empty($data['appointment_id'])) {
                $appointment = $row->plan->doctor->appointments()->whereKey($data['appointment_id'])
                    ->where('clinic_id', $row->plan->clinic_id)
                    ->where('patient_id', $row->plan->patient_id)
                    ->firstOrFail();
                $row->appointment_id = $appointment->id;
            }
            $row->stage = $data['stage'];
            $row->clinical_notes = $data['clinical_notes'] ?? $row->clinical_notes;
            if ($data['stage'] === 'completed') {
                $row->performed_at = now();
                $row->completed_by = Auth::guard('web')->id();
                if ($row->follow_up_after_days) {
                    $row->follow_up_due_at = now()->addDays($row->follow_up_after_days);
                }
            }
            $row->save();

            if ($data['stage'] !== 'completed') return $row;

            if ((int) $row->price > 0) {
                $accounting->recordTreatmentRevenue(
                    $row->plan->organization_id, $row->plan->clinic_id,
                    $row->provider_doctor_id ?: $doctor->id, (int) Auth::guard('web')->id(),
                    $row->id, $row->plan->id, (int) $row->price,
                    $row->title, $row->performed_at->toDateTimeString()
                );
            }

            if ($row->service_id) {
                $requirements = \App\Models\ClinicServiceInventoryRequirement::query()
                    ->where('service_id', $row->service_id)->with('inventoryItem')->get();
                foreach ($requirements as $requirement) {
                    $stock = ClinicInventoryItem::query()->whereKey($requirement->inventory_item_id)
                        ->where('clinic_id', $row->plan->clinic_id)->lockForUpdate()->firstOrFail();
                    $quantity = (float) $requirement->quantity;
                    abort_if((float) $stock->current_quantity < $quantity, 422,
                        'موجودی «' . $stock->name . '» برای تکمیل این خدمت کافی نیست.');
                    $stock->decrement('current_quantity', $quantity);
                    $movement = $stock->movements()->create([
                        'recorded_by' => Auth::guard('web')->id(),
                        'movement_type' => 'use',
                        'quantity' => $quantity,
                        'unit_cost' => $stock->average_unit_cost,
                        'reference' => 'TREATMENT-' . $row->id,
                        'notes' => 'مصرف خودکار برای خدمت: ' . $row->title,
                        'occurred_at' => $row->performed_at,
                    ]);
                    $accounting->recordInventoryMovement(
                        $row->plan->organization_id, $row->plan->clinic_id,
                        $row->provider_doctor_id ?: $doctor->id, (int) Auth::guard('web')->id(),
                        $movement->id, 'use',
                        (int) round($quantity * (int) $stock->average_unit_cost),
                        'مصرف مواد برای ' . $row->title, $row->performed_at->toDateTimeString()
                    );
                }
            }

            if ($row->follow_up_due_at) {
                PatientFollowUpTask::create([
                    'doctor_id' => $row->plan->doctor_id, 'clinic_id' => $row->plan->clinic_id,
                    'treatment_plan_id' => $row->plan->id,
                    'patient_id' => $row->plan->patient_id, 'appointment_id' => $row->appointment_id,
                    'created_by' => Auth::guard('web')->id(), 'title' => 'پیگیری پس از درمان: ' . $row->title,
                    'description' => 'پیگیری دوره‌ای ایجادشده از مرحله درمان؛ نتیجه و نیاز به مراجعه مجدد ثبت شود.',
                    'due_at' => $row->follow_up_due_at, 'priority' => 'normal',
                    'contact_method' => 'call',
                    'send_sms' => (bool) $row->plan->follow_up_sms_enabled && (bool) $row->plan->follow_up_sms_consent_confirmed,
                    'sms_consent_confirmed' => (bool) $row->plan->follow_up_sms_consent_confirmed,
                    'status' => 'pending',
                ]);
            }

            return $row->refresh();
        }, 3);

        $audit->handle('patient.treatment_item.updated', $updatedItem, [
            'doctor_id' => $doctor->id,
            'treatment_plan_id' => $updatedItem->treatment_plan_id,
            'changed_fields' => ['stage', 'appointment_id', 'clinical_notes'],
        ]);

        return back()->with('success', 'وضعیت مرحله درمان به‌روزرسانی شد.');
    }

    public function storeDentalChart(Request $request)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $dentition = $request->input('dentition');
        $validTeeth = $dentition === 'primary'
            ? ['51','52','53','54','55','61','62','63','64','65','71','72','73','74','75','81','82','83','84','85']
            : ['11','12','13','14','15','16','17','18','21','22','23','24','25','26','27','28','31','32','33','34','35','36','37','38','41','42','43','44','45','46','47','48'];
        $data = $request->validate([
            'patient_id' => ['required', 'integer'], 'clinic_id' => ['required', 'integer'],
            'dentition' => ['required', Rule::in(['permanent','primary'])],
            'tooth_number' => ['required', Rule::in($validTeeth)],
            'surface' => ['nullable', Rule::in(['M','D','O','I','B','L','F','P'])],
            'condition' => ['required', Rule::in(['healthy','caries','filled','crown','missing','root_canal','implant','fracture','watch'])],
            'severity' => ['nullable', Rule::in(['low','medium','high'])],
            'notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeOperationsClinicAccess($clinicId, $doctor);
        $patient = Patient::query()->whereKey($data['patient_id'])->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->where('clinic_id', $clinicId))->firstOrFail();
        PatientDentalChartEntry::create($data + [
            'doctor_id' => $doctor->id, 'recorded_by' => Auth::guard('web')->id(), 'recorded_at' => now(),
        ]);
        return back()->with('success', 'وضعیت دندان در نمودار بیمار ثبت شد.');
    }

    public function storeInventoryItem(Request $request, ClinicAccountingService $accounting)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('inventory.manage', $doctor);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer'], 'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:80'], 'unit' => ['required', 'string', 'max:24'],
            'minimum_quantity' => ['required', 'numeric', 'min:0', 'decimal:0,3'], 'opening_quantity' => ['nullable', 'numeric', 'min:0', 'decimal:0,3'],
            'unit_cost' => ['nullable', 'integer', 'min:0'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeOperationsClinicAccess($clinicId, $doctor);
        $organizationId = Address::whereKey($clinicId)->value('organization_id');
        DB::transaction(function () use ($data, $clinicId, $organizationId, $doctor, $accounting): void {
            $item = ClinicInventoryItem::create([
                'organization_id' => $organizationId, 'clinic_id' => $clinicId, 'name' => $data['name'],
                'sku' => $data['sku'] ?? null, 'unit' => $data['unit'], 'minimum_quantity' => $data['minimum_quantity'],
                'current_quantity' => $data['opening_quantity'] ?? 0, 'average_unit_cost' => $data['unit_cost'] ?? 0,
            ]);
            if (($data['opening_quantity'] ?? 0) > 0) {
                $movement = $item->movements()->create([
                    'recorded_by' => Auth::guard('web')->id(), 'movement_type' => 'opening',
                    'quantity' => $data['opening_quantity'], 'unit_cost' => $data['unit_cost'] ?? 0,
                    'occurred_at' => now(), 'notes' => 'موجودی افتتاحیه',
                ]);
                $accounting->recordInventoryMovement(
                    $organizationId, $clinicId, $doctor->id, (int) Auth::guard('web')->id(),
                    $movement->id, 'opening',
                    (int) round((float) $data['opening_quantity'] * (int) ($data['unit_cost'] ?? 0)),
                    'موجودی افتتاحیه: ' . $item->name, now()->toDateTimeString()
                );
            }
        }, 3);
        return back()->with('success', 'کالای انبار ثبت شد.');
    }

    public function moveStock(Request $request, int $item, ClinicAccountingService $accounting)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('inventory.manage', $doctor);
        $data = $request->validate([
            'movement_type' => ['required', Rule::in(['purchase','use','return','adjustment_in','adjustment_out'])],
            // مقدار با دقت سه رقم اعشار ذخیره می‌شود؛ اعشار بیشتر نباید بی‌صدا گرد شود.
            'quantity' => ['required', 'numeric', 'gt:0', 'decimal:0,3', 'max:999999'],
            'idempotency_key' => ['required', 'uuid'],
            'unit_cost' => ['required_if:movement_type,purchase', 'nullable', 'integer', 'min:0', 'max:999999999999'],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        // برای مصرف و اصلاحات خروجی، بهای میانگین انبار ملاک است.

        DB::transaction(function () use ($doctor, $item, $data, $accounting): void {
            $row = ClinicInventoryItem::query()->whereKey($item)
                ->whereIn('clinic_id', $this->accessibleOperationsClinicIds($doctor))
                ->lockForUpdate()->firstOrFail();

            // ارسال دوباره یک فرم نباید گردش کالا و سند حسابداری جدید بسازد.
            $existing = DB::table('clinic_inventory_movements')->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                abort_unless((int) $existing->inventory_item_id === (int) $row->id
                    && $existing->movement_type === $data['movement_type']
                    && round((float) $existing->quantity, 3) === round((float) $data['quantity'], 3)
                    && ($data['movement_type'] !== 'purchase' || (int) $existing->unit_cost === (int) ($data['unit_cost'] ?? 0)), 409,
                    'کلید تکرار درخواست قبلاً برای گردش دیگری استفاده شده است.');
                return;
            }

            $oldQuantity = round((float) $row->current_quantity, 3);
            abort_if($oldQuantity < 0, 409, 'موجودی فعلی انبار نامعتبر است؛ ابتدا مغایرت موجودی را بررسی کنید.');

            $quantity = (float) $data['quantity'];
            $incoming = in_array($data['movement_type'], ['purchase', 'return', 'adjustment_in'], true);
            $delta = $incoming ? $quantity : -$quantity;
            $newQuantity = round($oldQuantity + $delta, 3);
            abort_if($newQuantity < 0, 422, 'موجودی انبار برای این خروج کافی نیست.');

            $oldAverageCost = (int) $row->average_unit_cost;
            // برای خرید از قیمت فاکتور استفاده می‌شود؛ برگشت و اصلاح موجودی با بهای میانگین فعلی ارزش‌گذاری می‌شوند.
            $movementUnitCost = $data['movement_type'] === 'purchase'
                ? (int) $data['unit_cost']
                : $oldAverageCost;

            if ($data['movement_type'] === 'purchase' && $newQuantity > 0) {
                $oldInventoryValue = $oldQuantity * $oldAverageCost;
                $incomingInventoryValue = $quantity * $movementUnitCost;
                $row->average_unit_cost = (int) round(
                    ($oldInventoryValue + $incomingInventoryValue) / $newQuantity
                );
            }

            $row->current_quantity = $newQuantity;
            $row->save();

            $movement = $row->movements()->create([
                'recorded_by' => Auth::guard('web')->id(),
                'movement_type' => $data['movement_type'],
                'quantity' => $quantity,
                'unit_cost' => $movementUnitCost,
                'reference' => $data['reference'] ?? null,
                'idempotency_key' => $data['idempotency_key'],
                'notes' => $data['notes'] ?? null,
                'occurred_at' => now(),
            ]);

            // خروج کالا همیشه با بهای میانگین قبل از خروج ثبت می‌شود؛ قیمت ارسالی کاربر نباید هزینه مصرف را دستکاری کند.
            $cost = (int) round($quantity * ($incoming ? $movementUnitCost : $oldAverageCost));
            $accounting->recordInventoryMovement(
                $row->organization_id, $row->clinic_id, $doctor->id, (int) Auth::guard('web')->id(),
                $movement->id, $data['movement_type'], $cost, $row->name, now()->toDateTimeString()
            );
        }, 3);

        return back()->with('success', 'گردش انبار ثبت شد.');
    }

    public function createSettlement(Request $request)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:clinic_organizations,id'],
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'],
        ]);
        $organization = $doctor->clinicOrganizations()->whereKey($data['organization_id'])->wherePivot('status', 'active')->firstOrFail();
        abort_unless($organization->owner_user_id === Auth::guard('web')->id(), 403);
        $targetDoctor = $organization->doctors()->whereKey($data['doctor_id'])->wherePivot('status', 'active')->firstOrFail();
        $share = (int) ($targetDoctor->pivot->revenue_share_basis_points ?? 7000);
        $items = PatientTreatmentItem::query()->where('provider_doctor_id', $targetDoctor->id)->where('stage', 'completed')
            ->whereBetween('performed_at', [$data['period_start'] . ' 00:00:00', $data['period_end'] . ' 23:59:59'])
            ->whereHas('plan', fn ($q) => $q->where('organization_id', $organization->id))->get();
        $gross = (int) $items->sum('price');
        abort_if($gross < 1, 422, 'در بازه انتخاب‌شده خدمت انجام‌شده‌ای برای تسویه وجود ندارد.');
        $doctorShare = (int) $items->sum(fn ($item) => (int) round($item->price * (($item->doctor_share_basis_points ?? $share) / 10000)));
        DoctorSettlement::firstOrCreate([
            'organization_id' => $organization->id, 'doctor_id' => $targetDoctor->id,
            'period_start' => $data['period_start'], 'period_end' => $data['period_end'],
        ], [
            'gross_amount' => $gross, 'clinic_share_amount' => $gross - $doctorShare,
            'adjustment_amount' => 0, 'net_amount' => $doctorShare, 'status' => 'draft',
        ]);
        return back()->with('success', 'پیش‌نویس تسویه پزشک ساخته شد.');
    }

    public function approveSettlement(int $settlement)
    {
        $this->authorizeClinicRole();
        $this->authorizeDoctorOnly();
        DB::transaction(function () use ($settlement): void {
            $row = DoctorSettlement::query()->whereKey($settlement)->lockForUpdate()
                ->whereHas('organization', fn ($q) => $q->where('owner_user_id', Auth::guard('web')->id()))
                ->firstOrFail();
            abort_unless($row->status === 'draft', 409, 'این تسویه قبلاً بررسی شده است.');
            $row->update(['status' => 'approved', 'approved_by' => Auth::guard('web')->id()]);
        }, 3);
        return back()->with('success', 'تسویه تأیید شد.');
    }

    public function paySettlement(Request $request, int $settlement, ClinicAccountingService $accounting)
    {
        $this->authorizeClinicRole();
        $this->authorizeDoctorOnly();
        $payment = $request->validate([
            'payment_method' => ['required', Rule::in(['cash','card','transfer','online','cheque'])],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);
        DB::transaction(function () use ($settlement, $accounting, $payment): void {
            $row = DoctorSettlement::query()->whereKey($settlement)->lockForUpdate()
                ->whereHas('organization', fn ($q) => $q->where('owner_user_id', Auth::guard('web')->id()))
                ->firstOrFail();
            abort_unless($row->status === 'approved', 409, 'فقط تسویه تأییدشده قابل پرداخت است.');
            $row->update(['status' => 'paid', 'paid_by' => Auth::guard('web')->id(), 'paid_at' => now(), 'payment_method' => $payment['payment_method'], 'payment_reference' => $payment['payment_reference'] ?? null]);
            if ((int) $row->net_amount > 0) {
                $accounting->post(
                    $row->organization_id, null, $row->doctor_id, (int) Auth::guard('web')->id(),
                    'doctor_settlement', $row->id, 'تسویه سهم پزشک', now()->toDateTimeString(),
                    'doctor-settlement:' . $row->id,
                    [['code' => '5100', 'debit' => (int) $row->net_amount, 'credit' => 0],
                     ['code' => '1000', 'debit' => 0, 'credit' => (int) $row->net_amount]]
                );
            }
        }, 3);
        return back()->with('success', 'تسویه پزشک پرداخت و ثبت حسابداری شد.');
    }

    public function createPayroll(Request $request)
    {
        $this->authorizeClinicRole();
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'organization_id' => ['required', 'integer', 'exists:clinic_organizations,id'],
            'clinic_id' => ['nullable', 'integer'], 'staff_user_id' => ['required', 'integer', 'exists:users,id'],
            'period_start' => ['required', 'date'], 'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'gross_amount' => ['required', 'integer', 'min:0', 'max:999999999999'],
            'deduction_amount' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $organization = $doctor->clinicOrganizations()->whereKey($data['organization_id'])->wherePivot('status', 'active')->firstOrFail();
        abort_unless($organization->owner_user_id === Auth::guard('web')->id(), 403);
        $staff = $organization->members()->whereKey($data['staff_user_id'])->wherePivot('is_active', true)->firstOrFail();
        $isOrganizationOwner = (int) $staff->id === (int) $organization->owner_user_id;
        if (!$isOrganizationOwner) {
            abort_unless(!empty($data['clinic_id']), 422, 'برای حقوق کارمند، شعبه مجاز او را مشخص کنید.');
        }
        if (!empty($data['clinic_id'])) {
            abort_unless($organization->clinics()->whereKey($data['clinic_id'])->exists(), 403);
            if (!$isOrganizationOwner) {
                $assignedClinicIds = $staff->pivot->clinic_ids ?? [];
                if (is_string($assignedClinicIds)) $assignedClinicIds = json_decode($assignedClinicIds, true) ?: [];
                abort_unless(in_array((int) $data['clinic_id'], array_map('intval', (array) $assignedClinicIds), true), 403,
                    'این کارمند به شعبه انتخاب‌شده تخصیص ندارد.');
            }
        }
        $gross = (int) $data['gross_amount'];
        $deduction = (int) ($data['deduction_amount'] ?? 0);
        abort_if($deduction > $gross, 422, 'کسورات حقوق نمی‌تواند از مبلغ ناخالص بیشتر باشد.');
        ClinicStaffPayroll::firstOrCreate([
            'organization_id' => $organization->id,
            'clinic_id' => $data['clinic_id'] ?? null,
            'staff_user_id' => $staff->id,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
        ], [
            'clinic_id' => $data['clinic_id'] ?? null,
            'gross_amount' => $gross, 'deduction_amount' => $deduction, 'net_amount' => $gross - $deduction,
            'status' => 'draft', 'notes' => $data['notes'] ?? null,
        ]);
        return back()->with('success', 'پیش‌نویس حقوق کارکنان ثبت شد.');
    }

    public function approvePayroll(int $payroll)
    {
        $this->authorizeClinicRole();
        $this->authorizeDoctorOnly();
        DB::transaction(function () use ($payroll): void {
            $row = ClinicStaffPayroll::query()->whereKey($payroll)->lockForUpdate()
                ->whereHas('organization', fn ($q) => $q->where('owner_user_id', Auth::guard('web')->id()))
                ->firstOrFail();
            abort_unless($row->status === 'draft', 409, 'این فیش حقوقی قبلاً بررسی شده است.');
            $row->update(['status' => 'approved', 'approved_by' => Auth::guard('web')->id()]);
        }, 3);
        return back()->with('success', 'حقوق تأیید شد.');
    }

    public function payPayroll(Request $request, int $payroll, ClinicAccountingService $accounting)
    {
        $this->authorizeClinicRole();
        $this->authorizeDoctorOnly();
        $payment = $request->validate([
            'payment_method' => ['required', Rule::in(['cash','card','transfer','online','cheque'])],
            'payment_reference' => ['nullable', 'string', 'max:120'],
        ]);
        DB::transaction(function () use ($payroll, $accounting, $payment): void {
            $row = ClinicStaffPayroll::query()->whereKey($payroll)->lockForUpdate()
                ->whereHas('organization', fn ($q) => $q->where('owner_user_id', Auth::guard('web')->id()))
                ->firstOrFail();
            abort_unless($row->status === 'approved', 409, 'فقط فیش حقوقی تأییدشده قابل پرداخت است.');
            $row->update(['status' => 'paid', 'paid_by' => Auth::guard('web')->id(), 'paid_at' => now(), 'payment_method' => $payment['payment_method'], 'payment_reference' => $payment['payment_reference'] ?? null]);
            if ((int) $row->net_amount > 0) {
                $accounting->post(
                    $row->organization_id, $row->clinic_id, null, (int) Auth::guard('web')->id(),
                    'clinic_staff_payroll', $row->id, 'پرداخت حقوق کارکنان', now()->toDateTimeString(),
                    'staff-payroll:' . $row->id,
                    [['code' => '5200', 'debit' => (int) $row->net_amount, 'credit' => 0],
                     ['code' => '1000', 'debit' => 0, 'credit' => (int) $row->net_amount]]
                );
            }
        }, 3);
        return back()->with('success', 'حقوق پرداخت و در دفتر مالی ثبت شد.');
    }

    private function secretaryHas($doctor, string $permission): bool
    {
        $user = Auth::guard('web')->user();
        $assignment = $user?->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) $permissions = json_decode($permissions, true) ?: [];
        return is_array($permissions) && in_array($permission, $permissions, true);
    }
    private function accessibleOperationsClinicIds(\App\Models\Doctor $doctor): array
    {
        $ids = $this->accessibleClinicIds($doctor);
        $user = Auth::guard('web')->user();

        if ($user?->role === 'doctor') {
            $organizationIds = ClinicOrganization::query()
                ->where('owner_user_id', $user->id)
                ->where('status', 'active')
                ->pluck('id');
            if ($organizationIds->isNotEmpty()) {
                $ids = array_merge($ids, Address::query()
                    ->whereIn('organization_id', $organizationIds)
                    ->where('is_active', true)
                    ->pluck('id')->map(fn ($id) => (int) $id)->all());
            }
        }

        return array_values(array_unique(array_map('intval', $ids)));
    }

    private function authorizeOperationsClinicAccess(int $clinicId, \App\Models\Doctor $doctor): void
    {
        abort_unless(in_array($clinicId, $this->accessibleOperationsClinicIds($doctor), true), 403,
            'شما به این شعبه برای عملیات کلینیک دسترسی ندارید.');
    }

    private function authorizeClinicRole(): void
    {
        abort_unless(in_array(Auth::guard('web')->user()?->role, ['doctor', 'secretary'], true), 403);
    }

}
