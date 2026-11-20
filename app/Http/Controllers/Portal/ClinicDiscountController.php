<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicDiscountCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicDiscountController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('marketing.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);

        $codes = ClinicDiscountCode::query()->where('doctor_id', $doctor->id)
            ->where(fn ($query) => $query->whereNull('clinic_id')->orWhereIn('clinic_id', $clinicIds))
            ->with('clinic:id,name')
            ->withCount('redemptions')
            ->latest('id')->paginate(20)->withQueryString();

        return Inertia::render('Portal/Marketing/Discounts', [
            'doctor' => $doctor,
            'clinics' => Address::query()->where('doctor_id', $doctor->id)->whereIn('id', $clinicIds)->where('is_active', true)->orderBy('name')->get(['id','name']),
            'codes' => $codes,
            'canManage' => Auth::guard('web')->user()?->role === 'doctor'
                || $this->secretaryCanManageMarketing($doctor),
        ]);
    }

    public function store(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('marketing.manage', $doctor);
        $data = $request->validate([
            'code' => ['required','string','min:3','max:32','alpha_dash',Rule::unique('clinic_discount_codes','code')->where('doctor_id',$doctor->id)],
            'name' => ['required','string','max:160'],
            'clinic_id' => ['nullable','integer'],
            'discount_type' => ['required',Rule::in(['percent','fixed'])],
            'discount_value' => ['required','integer','min:1','max:1000000000000'],
            'minimum_amount_rial' => ['nullable','integer','min:0','max:1000000000000'],
            'maximum_discount_rial' => ['nullable','integer','min:1','max:1000000000000'],
            'usage_limit' => ['nullable','integer','min:1','max:1000000'],
            'per_patient_limit' => ['required','integer','between:1,20'],
            'starts_at' => ['nullable','date'],
            'ends_at' => ['nullable','date','after_or_equal:starts_at'],
        ]);
        if ($data['discount_type'] === 'percent') {
            abort_if((int) $data['discount_value'] > 100, 422, 'درصد تخفیف نمی‌تواند بیش از ۱۰۰ باشد.');
        }
        if (!empty($data['clinic_id'])) $this->authorizeClinicAccess((int) $data['clinic_id'], $doctor);

        $code = ClinicDiscountCode::query()->create([
            ...$data,
            'doctor_id' => $doctor->id,
            'created_by_user_id' => (int) Auth::guard('web')->id(),
            'code' => Str::upper(trim($data['code'])),
            'minimum_amount_rial' => $data['minimum_amount_rial'] ?? 0,
            'status' => 'active',
        ]);
        $audit->handle('marketing.discount_code.created', $code, [
            'doctor_id' => $doctor->id, 'clinic_id' => $code->clinic_id,
            'discount_type' => $code->discount_type, 'changed_fields' => ['discount_code_created'],
        ]);

        return back()->with('success', 'کد تخفیف ساخته شد و در رزرو عمومی قابل استفاده است.');
    }

    public function toggle(ClinicDiscountCode $code, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('marketing.manage', $doctor);
        abort_unless((int) $code->doctor_id === (int) $doctor->id, 404);
        if ($code->clinic_id) $this->authorizeClinicAccess((int) $code->clinic_id, $doctor);

        $status = $code->status === 'active' ? 'paused' : 'active';
        $code->update(['status' => $status]);
        $audit->handle('marketing.discount_code.status_changed', $code, [
            'doctor_id' => $doctor->id, 'status' => $status, 'changed_fields' => ['status'],
        ]);

        return back()->with('success', $status === 'active' ? 'کد تخفیف فعال شد.' : 'کد تخفیف متوقف شد.');
    }

    private function secretaryCanManageMarketing($doctor): bool
    {
        $user = Auth::guard('web')->user();
        if ($user?->role !== 'secretary') return false;
        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) $permissions = json_decode($permissions, true) ?: [];
        return is_array($permissions) && in_array('marketing.manage', $permissions, true);
    }
}
