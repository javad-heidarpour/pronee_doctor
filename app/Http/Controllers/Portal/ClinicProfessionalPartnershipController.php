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
use App\Models\ClinicProfessionalDue;
use Carbon\Carbon;
use App\Models\MedicalNetworkProvider;
use App\Services\ClinicProfessionalSettlementCalculator;
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
        abort_unless($user && in_array($user->role, ['doctor', 'secretary', 'user'], true), 403);

        if ($user->role === 'user') {
            $providerIds = MedicalNetworkProvider::query()->where('created_by_user_id', $user->id)->pluck('id');
            abort_if($providerIds->isEmpty(), 403, 'برای این حساب کاربری پروفایل حرفه‌ای ثبت نشده است.');

            $ownedPartnerships = ClinicProfessionalPartnership::query()->whereIn('provider_id', $providerIds)
                ->with(['provider:id,name,provider_type', 'clinic:id,name', 'doctor:id,name,family'])
                ->latest()->get();
            $professionalSessions = ClinicProfessionalSession::query()->whereIn('provider_id', $providerIds)
                ->with(['provider:id,name,provider_type', 'clinic:id,name', 'partnership:id,model,service_name,payment_direction', 'doctor:id,name,family'])
                ->latest('service_date')->limit(100)->get();
            $professionalDues = ClinicProfessionalDue::query()->where('professional_user_id', $user->id)
                ->with(['partnership.provider:id,name', 'partnership.clinic:id,name'])->latest('due_date')->limit(100)->get();

            return Inertia::render('Portal/ProfessionalPartnerships/Index', [
                'partnerships' => collect(),
                'incoming' => $ownedPartnerships,
                'sessions' => $professionalSessions,
                'dues' => $professionalDues,
                'providers' => collect(),
                'clinics' => collect(),
                'stats' => [
                    'active' => $ownedPartnerships->where('status', 'active')->count(),
                    'pending' => $ownedPartnerships->where('status', 'proposed')->count(),
                    'periodic_due' => $professionalDues->where('status', '!=', 'confirmed')->sum('amount_rial'),
                    'unpaid' => $professionalSessions->whereIn('status', ['pending', 'partially_confirmed'])->sum('settlement_amount_rial'),
                    'paid' => $professionalSessions->where('status', 'paid')->sum('settlement_amount_rial'),
                ],
                'canManage' => false,
            ]);
        }

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
            ->whereHas('provider', fn ($q) => $q->where('created_by_user_id', $user->id))
            ->with(['provider:id,name', 'clinic:id,name', 'doctor:id,name,family'])
            ->latest()->get();

        $sessions = ClinicProfessionalSession::query()
            ->where(function ($query) use ($doctor, $clinicIds, $user): void {
                $query->where(fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds))
                    ->orWhereHas('provider', fn ($q) => $q->where('created_by_user_id', $user->id));
            })
            ->with(['provider:id,name,provider_type,created_by_user_id', 'clinic:id,name', 'doctor:id,user_id,name,family', 'partnership:id,model,service_name,payment_direction'])
            ->latest('service_date')->limit(100)->get();

        $providers = MedicalNetworkProvider::query()
            ->where('status', 'active')->where('is_verified', true)
            ->where('created_by_user_id', '!=', $user->id)
            ->orderBy('name')->get(['id', 'name', 'provider_type', 'city', 'specialties', 'license_number']);

        return Inertia::render('Portal/ProfessionalPartnerships/Index', [
            'partnerships' => $partnerships,
            'incoming' => $incoming,
            'sessions' => $sessions,
            'dues' => ClinicProfessionalDue::query()->where(function ($query) use ($doctor, $clinicIds, $user): void {
                $query->whereHas('partnership', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds))
                    ->orWhere('professional_user_id', $user->id);
            })->with(['partnership.provider:id,name', 'partnership.clinic:id,name'])->latest('due_date')->limit(100)->get(),
            'providers' => $providers,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'active' => (clone $partnerships)->where('status', 'active')->count(),
                'pending' => (clone $partnerships)->where('status', 'proposed')->count(),
                'periodic_due' => ClinicProfessionalDue::query()->whereHas('partnership', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds))->where('status', '!=', 'confirmed')->sum('amount_rial'),
                'unpaid' => $sessions->whereIn('status', ['pending', 'partially_confirmed'])->sum('settlement_amount_rial'),
                'paid' => $sessions->where('status', 'paid')->sum('settlement_amount_rial'),
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
        abort_if((int) $provider->created_by_user_id === (int) $user->id, 422, 'برای قرارداد همکاری، متخصص باید حساب مستقل از حساب مالک کلینیک داشته باشد.');
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

    public function storeSession(Request $request, RecordSecurityAuditEventAction $audit, ClinicProfessionalSettlementCalculator $calculator)
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

        $session = DB::transaction(function () use ($data, $doctor, $user, $calculator): ClinicProfessionalSession {
            $agreement = ClinicProfessionalPartnership::query()
                ->whereKey($data['partnership_id'])->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
                ->where('status', 'active')->whereIn('model', ['percentage', 'fixed_per_session', 'hybrid'])->whereDate('starts_at', '<=', $data['service_date'])
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $data['service_date']))
                ->lockForUpdate()->firstOrFail();

            abort_if((int) $agreement->provider()->value('created_by_user_id') === (int) $agreement->doctor()->value('user_id'), 422, 'برای ثبت تسویه دوطرفه، مالک حساب متخصص و کلینیک باید مستقل باشند.');

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

            $shares = $calculator->split(
                $agreement->model,
                $net,
                (int) $agreement->professional_share_bps,
                (int) $agreement->fixed_fee_rial
            );
            $professionalAmount = $shares['professional'];
            $clinicAmount = $shares['clinic'];
            $settlementAmount = $calculator->payableAmount($shares, $agreement->payment_direction);

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
                'settlement_amount_rial' => $settlementAmount,
                'status' => 'pending',
            ]);
        }, 3);

        $audit->handle('clinic_professional_partnership.session_recorded', $session, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['session_recorded'],
        ]);
        return back()->with('success', 'خدمت ثبت شد و سهم طرفین بر اساس قرارداد محاسبه گردید.');
    }

    public function confirmSession(Request $request, ClinicProfessionalSession $session, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $data = $request->validate(['payment_reference' => ['nullable', 'string', 'max:120']]);
        $result = DB::transaction(function () use ($session, $user, $data): string {
            $locked = ClinicProfessionalSession::query()->whereKey($session->id)->lockForUpdate()->with(['partnership', 'provider', 'doctor'])->firstOrFail();
            $clinicOwnerId = (int) $locked->doctor?->user_id;
            $professionalOwnerId = (int) $locked->provider?->created_by_user_id;
            $side = null;
            if ((int) $user->id === $professionalOwnerId) {
                $side = 'professional';
            } elseif ((int) $user->id === $clinicOwnerId) {
                $side = 'clinic';
            } elseif ($user->role === 'secretary') {
                $doctor = $this->currentDoctor();
                abort_unless((int) $doctor->id === (int) $locked->doctor_id, 403);
                $this->authorizeSecretaryPermission('finance.manage', $doctor);
                abort_unless(in_array((int) $locked->clinic_id, $this->accessibleClinicIds($doctor), true), 403);
                $side = 'clinic';
            }
            abort_unless($side, 403, 'فقط نماینده مجاز کلینیک یا متخصص می‌تواند تأیید کند.');
            abort_unless($locked->status !== 'paid', 422, 'این تسویه قبلاً نهایی شده است.');
            $payerSide = $locked->partnership?->payment_direction === 'professional_to_clinic' ? 'professional' : 'clinic';
            $prefix = $side === $payerSide ? 'payer' : 'receiver';
            abort_if($locked->{$prefix . '_confirmed_at'}, 422, 'تأیید این سمت قبلاً ثبت شده است.');
            $locked->{$prefix . '_confirmed_at'} = now();
            $locked->{$prefix . '_confirmed_by_user_id'} = $user->id;
            $locked->{$prefix . '_reference'} = $data['payment_reference'] ?? null;
            $both = (bool) $locked->payer_confirmed_at && (bool) $locked->receiver_confirmed_at;
            $locked->status = $both ? 'paid' : 'partially_confirmed';
            if ($both) {
                $locked->paid_at = now();
                $locked->paid_by_user_id = $user->id;
            }
            $locked->save();
            return $locked->status;
        }, 3);
        $audit->handle('clinic_professional_partnership.session_confirmation', $session, ['changed_fields' => ['settlement_confirmation']]);
        return back()->with('success', $result === 'paid' ? 'تسویه با تأیید هر دو طرف نهایی شد.' : 'تأیید شما ثبت شد؛ تأیید طرف مقابل باقی مانده است.');
    }

    public function generateDue(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate([
            'partnership_id' => ['required', 'integer'],
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'due_date' => ['required', 'date', 'after_or_equal:period_end'],
        ]);
        $due = DB::transaction(function () use ($data, $doctor, $user): ClinicProfessionalDue {
            $agreement = ClinicProfessionalPartnership::query()->whereKey($data['partnership_id'])
                ->where('doctor_id', $doctor->id)->whereIn('clinic_id', $this->accessibleClinicIds($doctor))
                ->where('status', 'active')->whereIn('model', ['room_rent', 'shift_fee', 'monthly_fixed'])
                ->whereDate('starts_at', '<=', $data['period_start'])
                ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', $data['period_end']))
                ->lockForUpdate()->firstOrFail();
            $periodStart = Carbon::parse($data['period_start'])->startOfDay();
            $periodEnd = Carbon::parse($data['period_end'])->startOfDay();
            $expectedEnd = $agreement->periodic_cycle === 'weekly'
                ? $periodStart->copy()->addDays(6)
                : $periodStart->copy()->addMonthNoOverflow()->subDay();
            abort_if(!$periodEnd->isSameDay($expectedEnd), 422, 'بازه صورتحساب باید دقیقاً با چرخه هفتگی یا ماهانه قرارداد منطبق باشد.');
            abort_if(Carbon::parse($data['due_date'])->lt($periodEnd), 422, 'سررسید صورتحساب نمی‌تواند قبل از پایان دوره باشد.');
            $clinicOwner = (int) $agreement->doctor()->value('user_id');
            $professionalOwner = (int) $agreement->provider()->value('created_by_user_id');
            abort_if(!$clinicOwner || !$professionalOwner || $clinicOwner === $professionalOwner, 422, 'برای تأیید دوطرفه باید مالک حساب کلینیک و متخصص مشخص و متفاوت باشند.');
            return ClinicProfessionalDue::query()->firstOrCreate([
                'partnership_id' => $agreement->id,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ], [
                'doctor_id' => $doctor->id, 'clinic_id' => $agreement->clinic_id,
                'provider_id' => $agreement->provider_id,
                'clinic_user_id' => $clinicOwner, 'professional_user_id' => $professionalOwner,
                'created_by_user_id' => $user->id, 'due_date' => $data['due_date'],
                'amount_rial' => $agreement->periodic_fee_rial,
                'payment_direction' => $agreement->payment_direction,
                'status' => 'pending',
            ]);
        }, 3);
        $audit->handle('clinic_professional_partnership.periodic_due_generated', $due, ['changed_fields' => ['due_created']]);
        return back()->with('success', $due->wasRecentlyCreated ? 'صورتحساب دوره‌ای ایجاد شد.' : 'این دوره قبلاً صورتحساب‌گذاری شده است؛ رکورد تکراری ساخته نشد.');
    }

    public function confirmDue(Request $request, ClinicProfessionalDue $due, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $data = $request->validate(['payment_reference' => ['nullable', 'string', 'max:120']]);
        $result = DB::transaction(function () use ($due, $user, $data): string {
            $locked = ClinicProfessionalDue::query()->whereKey($due->id)->lockForUpdate()->firstOrFail();
            $side = null;
            if ((int) $user->id === (int) $locked->professional_user_id) {
                $side = 'professional';
            } elseif ((int) $user->id === (int) $locked->clinic_user_id) {
                $side = 'clinic';
            } elseif ($user->role === 'secretary') {
                $doctor = $this->currentDoctor();
                abort_unless((int) $doctor->id === (int) $locked->doctor_id, 403);
                $this->authorizeSecretaryPermission('finance.manage', $doctor);
                abort_unless(in_array((int) $locked->clinic_id, $this->accessibleClinicIds($doctor), true), 403);
                $side = 'clinic';
            }
            abort_unless($side, 403, 'فقط نماینده مجاز کلینیک یا متخصص می‌تواند تأیید کند.');
            abort_unless($locked->status !== 'confirmed', 422, 'این صورتحساب قبلاً نهایی شده است.');
            $payerSide = $locked->payment_direction === 'professional_to_clinic' ? 'professional' : 'clinic';
            $prefix = $side === $payerSide ? 'payer' : 'receiver';
            abort_if($locked->{$prefix . '_confirmed_at'}, 422, 'تأیید این سمت قبلاً ثبت شده است.');
            $locked->{$prefix . '_confirmed_at'} = now();
            $locked->{$prefix . '_confirmed_by_user_id'} = $user->id;
            $locked->{$prefix . '_reference'} = $data['payment_reference'] ?? null;
            $both = (bool) $locked->payer_confirmed_at && (bool) $locked->receiver_confirmed_at;
            $locked->status = $both ? 'confirmed' : 'partially_confirmed';
            $locked->save();
            return $locked->status;
        }, 3);
        $audit->handle('clinic_professional_partnership.periodic_due_confirmation', $due, ['changed_fields' => ['confirmation_status']]);
        return back()->with('success', $result === 'confirmed' ? 'صورتحساب با تأیید دوطرفه نهایی شد.' : 'تأیید شما ثبت شد؛ تأیید طرف مقابل باقی مانده است.');
    }

    public function invoice(ClinicProfessionalDue $due)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $due->load(['partnership.provider', 'partnership.clinic', 'partnership.doctor']);
        $isProfessional = (int) $user->id === (int) $due->professional_user_id;
        $isClinicOwner = (int) $user->id === (int) $due->clinic_user_id;
        $isSecretary = false;
        if ($user->role === 'secretary') {
            $doctor = $this->currentDoctor();
            $isSecretary = (int) $doctor->id === (int) $due->doctor_id
                && in_array((int) $due->clinic_id, $this->accessibleClinicIds($doctor), true);
            if ($isSecretary) $this->authorizeSecretaryPermission('finance.view', $doctor);
        }
        abort_unless($isProfessional || $isClinicOwner || $isSecretary, 403);
        return response()->view('portal.professional-partnership-invoice', ['due' => $due]);
    }

    private function secretaryCanManage($doctor): bool
    {
        $assignment = Auth::guard('web')->user()->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) $permissions = json_decode($permissions, true) ?: [];
        return is_array($permissions) && in_array('finance.manage', $permissions, true);
    }
}
