<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\Appointment;
use App\Models\ClinicProfessionalPartnership;
use App\Models\ClinicProfessionalSession;
use App\Models\MedicalNetworkProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicProfessionalPartnershipController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        $clinicIds = $this->accessibleClinicIds($doctor);
        if ($user->role === 'secretary') {
            $this->authorizeSecretaryPermission('finance.view', $doctor);
        }

        $partnerships = ClinicProfessionalPartnership::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->with(['provider:id,name,provider_type,specialties', 'clinic:id,name'])
            ->withCount('sessions')
            ->latest()->get();

        $incoming = ClinicProfessionalPartnership::query()
            ->where('doctor_id', $doctor->id)
            ->whereHas('provider', fn ($q) => $q->where('created_by_user_id', '!=', $user->id))
            ->with(['provider:id,name', 'clinic:id,name', 'doctor:id,name,family'])
            ->latest()->get();

        $sessions = ClinicProfessionalSession::query()
            ->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)
            ->with(['provider:id,name,provider_type', 'clinic:id,name', 'partnership:id,model,service_name'])
            ->latest('service_date')->limit(100)->get();

        $providers = MedicalNetworkProvider::query()
            ->where('status', 'active')->where('is_verified', true)
            ->orderBy('name')->get(['id', 'name', 'provider_type', 'city', 'specialties', 'license_number']);

        return Inertia::render('Portal/ProfessionalPartnerships/Index', [
            'partnerships' => $partnerships,
            'incoming' => $incoming,
            'sessions' => $sessions,
            'providers' => $providers,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'active' => (clone $partnerships)->where('status', 'active')->count(),
                'pending' => (clone $partnerships)->where('status', 'proposed')->count(),
                'unpaid' => (clone $sessions)->where('status', 'pending')->sum('professional_amount_rial'),
                'paid' => (clone $sessions)->where('status', 'paid')->sum('professional_amount_rial'),
            ],
            'canManage' => $user->role !== 'secretary' || $this->secretaryCanManage($doctor),
        ]);
    }

    public function store(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);

        $data = $request->validate([
            'clinic_id' => ['required', 'integer'],
            'provider_id' => ['required', 'integer', Rule::exists('medical_network_providers', 'id')->where('status', 'active')->where('is_verified', true)],
            'model' => ['required', Rule::in(['percentage', 'fixed_per_session', 'hybrid', 'room_rent', 'shift_fee', 'monthly_fixed'])],
            'service_name' => ['required', 'string', 'max:160'],
            'service_fee_rial' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'professional_share_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'fixed_fee_rial' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'periodic_fee_rial' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'periodic_cycle' => ['nullable', Rule::in(['weekly', 'monthly'])],
            'payment_direction' => ['required', Rule::in(['clinic_to_professional', 'professional_to_clinic'])],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'terms' => ['nullable', 'string', 'max:3000'],
        ]);

        $clinicId = (int) $data['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $provider = MedicalNetworkProvider::query()->whereKey($data['provider_id'])
            ->where('status', 'active')->where('is_verified', true)->firstOrFail();
        $shareBps = (int) round(((float) ($data['professional_share_percent'] ?? 70)) * 100);
        $clinicBps = 10000 - $shareBps;
        $model = $data['model'];
        if (in_array($model, ['percentage', 'hybrid'], true)) {
            abort_if($shareBps < 1 || $clinicBps < 1, 422, 'برای مدل درصدی، سهم هر دو طرف باید بیشتر از صفر باشد.');
        }
        if (in_array($model, ['room_rent', 'shift_fee', 'monthly_fixed'], true)) {
            abort_unless((int) ($data['periodic_fee_rial'] ?? 0) > 0 && filled($data['periodic_cycle'] ?? null), 422, 'برای اجاره یا حق‌الزحمه دوره‌ای، مبلغ و دوره الزامی است.');
        }
        if (in_array($model, ['fixed_per_session', 'hybrid'], true)) {
            abort_unless((int) ($data['fixed_fee_rial'] ?? 0) > 0, 422, 'مبلغ ثابت هر جلسه الزامی است.');
        }

        $partnership = ClinicProfessionalPartnership::query()->create([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'provider_id' => $provider->id,
            'created_by_user_id' => $user->id,
            'model' => $model,
            'service_name' => $data['service_name'],
            'service_fee_rial' => (int) ($data['service_fee_rial'] ?? 0),
            'professional_share_bps' => $shareBps,
            'clinic_share_bps' => $clinicBps,
            'fixed_fee_rial' => (int) ($data['fixed_fee_rial'] ?? 0),
            'periodic_fee_rial' => (int) ($data['periodic_fee_rial'] ?? 0),
            'periodic_cycle' => $data['periodic_cycle'] ?? null,
            'payment_direction' => $data['payment_direction'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'status' => (int) $provider->created_by_user_id === (int) $user->id ? 'active' : 'proposed',
            'accepted_at' => (int) $provider->created_by_user_id === (int) $user->id ? now() : null,
            'terms' => $data['terms'] ?? null,
        ]);

        $audit->handle('clinic_professional_partnership.proposed', $partnership, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['partnership_proposed'],
        ]);

        return back()->with('success', (int) $provider->created_by_user_id === (int) $user->id ? 'همکاری داخلی ثبت و فعال شد.' : 'پیشنهاد همکاری ارسال شد؛ پس از پذیرش طرف مقابل فعال می‌شود.');
    }

    public function respond(Request $request, ClinicProfessionalPartnership $partnership, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $data = $request->validate(['decision' => ['required', Rule::in(['accept', 'reject'])]]);

        DB::transaction(function () use ($partnership, $user, $data): void {
            $locked = ClinicProfessionalPartnership::query()->whereKey($partnership->id)
                ->lockForUpdate()->with('provider')->firstOrFail();
            abort_unless((int) $locked->provider?->created_by_user_id === (int) $user->id, 403);
            abort_unless($locked->status === 'proposed', 422, 'این پیشنهاد قبلاً بررسی شده است.');
            $locked->update([
                'status' => $data['decision'] === 'accept' ? 'active' : 'rejected',
                'accepted_at' => $data['decision'] === 'accept' ? now() : null,
            ]);
        }, 3);

        $audit->handle('clinic_professional_partnership.responded', $partnership, ['changed_fields' => ['status_changed']]);
        return back()->with('success', $data['decision'] === 'accept' ? 'همکاری فعال شد.' : 'پیشنهاد رد شد.');
    }

    public function storeSession(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);

        $data = $request->validate([
            'partnership_id' => ['required', 'integer'],
            'service_date' => ['required', 'date', 'before_or_equal:today'],
            'gross_amount_rial' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'adjustment_amount_rial' => ['nullable', 'integer', 'min:0', 'max:999999999999'],
            'appointment_id' => ['nullable', 'integer'],
        ]);

        $session = DB::transaction(function () use ($data, $doctor, $user): ClinicProfessionalSession {
            $agreement = ClinicProfessionalPartnership::query()
                ->whereKey($data['partnership_id'])->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
                ->where('status', 'active')->whereIn('model', ['percentage', 'fixed_per_session', 'hybrid'])->whereDate('starts_at', '<=', $data['service_date'])
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $data['service_date']))
                ->lockForUpdate()->firstOrFail();

            $appointment = null;
            if (!empty($data['appointment_id'])) {
                $appointment = Appointment::query()->whereKey($data['appointment_id'])
                    ->where('doctor_id', $doctor->id)->where('clinic_id', $agreement->clinic_id)
                    ->where('status', 'visited')->lockForUpdate()->firstOrFail();
                abort_if(ClinicProfessionalSession::query()->where('appointment_id', $appointment->id)->exists(), 422, 'برای این نوبت قبلاً سهم همکاری ثبت شده است.');
            }

            $gross = (int) $data['gross_amount_rial'];
            $adjustment = (int) ($data['adjustment_amount_rial'] ?? 0);
            abort_if($adjustment > $gross, 422, 'تعدیل نمی‌تواند از مبلغ خدمت بیشتر باشد.');
            $net = $gross - $adjustment;

            $professionalAmount = match ($agreement->model) {
                'percentage' => (int) floor($net * $agreement->professional_share_bps / 10000),
                'fixed_per_session' => min($net, (int) $agreement->fixed_fee_rial),
                'hybrid' => min($net, (int) floor($net * $agreement->professional_share_bps / 10000) + (int) $agreement->fixed_fee_rial),
                default => 0,
            };
            $clinicAmount = $net - $professionalAmount;

            return ClinicProfessionalSession::query()->create([
                'partnership_id' => $agreement->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $agreement->clinic_id,
                'provider_id' => $agreement->provider_id,
                'appointment_id' => $appointment?->id,
                'created_by_user_id' => $user->id,
                'service_name' => $agreement->service_name,
                'service_date' => $data['service_date'],
                'gross_amount_rial' => $gross,
                'adjustment_amount_rial' => $adjustment,
                'professional_amount_rial' => $professionalAmount,
                'clinic_amount_rial' => $clinicAmount,
                'status' => 'pending',
            ]);
        }, 3);

        $audit->handle('clinic_professional_partnership.session_recorded', $session, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['session_recorded'],
        ]);
        return back()->with('success', 'خدمت ثبت شد و سهم طرفین بر اساس قرارداد محاسبه گردید.');
    }

    public function markPaid(Request $request, ClinicProfessionalSession $session, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate(['payment_reference' => ['nullable', 'string', 'max:120']]);

        DB::transaction(function () use ($session, $user, $doctor, $data): void {
            $locked = ClinicProfessionalSession::query()->whereKey($session->id)->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'pending', 422, 'این ردیف قبلاً تسویه شده است.');
            $locked->update([
                'status' => 'paid',
                'paid_at' => now(),
                'paid_by_user_id' => $user->id,
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);
        }, 3);

        $audit->handle('clinic_professional_partnership.session_paid', $session, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['status_changed'],
        ]);
        return back()->with('success', 'وضعیت تسویه داخلی ثبت شد؛ این عملیات تأیید بانکی یا انتقال وجه نیست.');
    }

    private function secretaryCanManage($doctor): bool
    {
        $assignment = Auth::guard('web')->user()->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) $permissions = json_decode($permissions, true) ?: [];
        return is_array($permissions) && in_array('finance.manage', $permissions, true);
    }
}
