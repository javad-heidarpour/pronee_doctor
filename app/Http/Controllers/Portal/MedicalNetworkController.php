<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Services\MedicalReferralWorkflow;
use App\Services\ReferralNetworkIntelligence;
use App\Services\ReferralCollaborationOpportunityFinder;
use App\Models\Address;
use App\Models\MedicalNetworkProvider;
use App\Models\MedicalReferral;
use App\Models\MedicalReferralEvent;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class MedicalNetworkController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request, ReferralNetworkIntelligence $intelligence, ReferralCollaborationOpportunityFinder $opportunityFinder)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary', 'admin'], true), 403);
        $doctor = $this->currentDoctor();
        $clinicIds = $this->accessibleClinicIds($doctor);
        if ($user->role === 'secretary') {
            $this->authorizeSecretaryPermission('appointments.view', $doctor);
        }

        $providerQuery = MedicalNetworkProvider::query()
            ->where('status', 'active')
            ->where('is_verified', true)
            ->when($request->filled('type'), fn ($q) => $q->where('provider_type', $request->string('type')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', 'like', '%' . $request->string('city') . '%'))
            ->when($request->filled('search'), function ($q) use ($request): void {
                $term = trim((string) $request->string('search'));
                $q->where(function ($nested) use ($term): void {
                    $nested->where('name', 'like', '%' . $term . '%')
                        ->orWhere('description', 'like', '%' . $term . '%')
                        ->orWhere('address', 'like', '%' . $term . '%');
                });
            })
            ->with('organization:id,name')
            ->orderBy('name');

        $networkProviders = (clone $providerQuery)->get(['id', 'name', 'provider_type', 'city']);
        $networkIntelligence = $intelligence->forProviders($networkProviders);
        $collaborationOpportunities = $opportunityFinder->find(
            (int) $doctor->id,
            (int) $user->id,
            $doctor->city?->name,
            clinicIds: $clinicIds
        );
        $providers = (clone $providerQuery)->paginate(12)->withQueryString();

        $outgoing = MedicalReferral::query()
            ->where('referring_doctor_id', $doctor->id)
            ->forClinics($clinicIds)
            ->with(['patient:id,name,phone', 'targetProvider:id,name,provider_type,city', 'sourceClinic:id,name', 'events' => fn ($q) => $q->orderByDesc('created_at')->limit(5)])
            ->latest()->limit(40)->get();

        $incoming = MedicalReferral::query()
            ->whereHas('targetProvider', fn ($q) => $q->where('created_by_user_id', $user->id))
            ->with(['patient:id,name', 'referringDoctor:id,name,family', 'targetProvider:id,name,provider_type', 'events' => fn ($q) => $q->orderByDesc('created_at')->limit(5)])
            ->latest()->limit(40)->get();

        return Inertia::render('Portal/MedicalNetwork/Index', [
            'providers' => $providers,
            'networkIntelligence' => $networkIntelligence,
            'collaborationOpportunities' => $collaborationOpportunities,
            'outgoing' => $outgoing,
            'incoming' => $incoming,
            'patients' => Patient::query()->where('doctor_id', $doctor->id)
                ->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))
                ->orderBy('name')->get(['id', 'name', 'phone']),
            'clinics' => Address::query()->whereIn('id', $clinicIds)->orderBy('name')->get(['id', 'name']),
            'stats' => [
                'outgoing_open' => MedicalReferral::query()->where('referring_doctor_id', $doctor->id)->forClinics($clinicIds)->whereIn('status', ['sent','accepted','scheduled','in_progress'])->count(),
                'incoming_open' => MedicalReferral::query()->whereHas('targetProvider', fn ($q) => $q->where('created_by_user_id', $user->id))->whereIn('status', ['sent','accepted','scheduled','in_progress'])->count(),
                'overdue' => MedicalReferral::query()->where(function ($q) use ($doctor, $user, $clinicIds): void {
                    $q->where(function ($outgoing) use ($doctor, $clinicIds): void {
                        $outgoing->where('referring_doctor_id', $doctor->id)->forClinics($clinicIds);
                    })->orWhereHas('targetProvider', fn ($provider) => $provider->where('created_by_user_id', $user->id));
                })->where(function ($q): void {
                    $q->where(fn ($sent) => $sent->where('status','sent')->where('created_at','<=',now()->subHours(48)))
                      ->orWhere(fn ($scheduled) => $scheduled->whereIn('status',['accepted','scheduled'])->whereNotNull('scheduled_at')->where('scheduled_at','<',now()));
                })->count(),
                'completed_month' => MedicalReferral::query()->where('referring_doctor_id', $doctor->id)->forClinics($clinicIds)->where('status','completed')->whereBetween('completed_at',[now()->startOfMonth(),now()->endOfMonth()])->count(),
            ],
            'filters' => $request->only(['type', 'city', 'search']),
            'providerTypes' => [
                ['value' => 'doctor', 'label' => 'پزشک'],
                ['value' => 'psychiatrist', 'label' => 'روان‌پزشک'],
                ['value' => 'psychologist', 'label' => 'روان‌شناس'],
                ['value' => 'counselor', 'label' => 'مشاور'],
                ['value' => 'social_worker', 'label' => 'مددکار اجتماعی'],
                ['value' => 'speech_therapist', 'label' => 'گفتاردرمانگر'],
                ['value' => 'occupational_therapist', 'label' => 'کاردرمانگر'],
                ['value' => 'nutritionist', 'label' => 'متخصص تغذیه'],
                ['value' => 'nurse', 'label' => 'پرستار'],
                ['value' => 'midwife', 'label' => 'ماما'],
                ['value' => 'physiotherapist', 'label' => 'فیزیوتراپیست'],
                ['value' => 'laboratory', 'label' => 'آزمایشگاه'],
                ['value' => 'imaging', 'label' => 'تصویربرداری'],
                ['value' => 'rehabilitation', 'label' => 'مرکز توان‌بخشی'],
                ['value' => 'other', 'label' => 'سایر گروه‌ها'],
            ],
        ]);
    }

    public function storeProvider(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor', 403);
        $doctor = $this->currentDoctor();

        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['doctor', 'psychiatrist', 'psychologist', 'counselor', 'social_worker', 'speech_therapist', 'occupational_therapist', 'nutritionist', 'nurse', 'midwife', 'physiotherapist', 'laboratory', 'imaging', 'rehabilitation', 'other'])],
            'name' => ['required', 'string', 'max:180'],
            'city' => ['nullable', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:45'],
            'email' => ['nullable', 'email', 'max:255'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'specialties' => ['nullable', 'array', 'max:30'],
            'specialties.*' => ['string', 'max:100'],
            'services' => ['nullable', 'array', 'max:50'],
            'services.*' => ['string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
        ]);

        $provider = MedicalNetworkProvider::query()->create([
            ...$data,
            'created_by_user_id' => $user->id,
            'doctor_id' => $doctor->id,
            'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(8)),
            'status' => 'pending',
            'is_verified' => false,
        ]);

        $audit->handle('medical_network.provider_submitted', $provider, ['doctor_id' => $doctor->id, 'changed_fields' => ['provider_submitted']]);
        return back()->with('success', 'مرکز برای بررسی و تأیید در شبکه ثبت شد. پس از تأیید، در جست‌وجوی همکاران نمایش داده می‌شود.');
    }

    public function storeReferral(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        if ($user->role === 'secretary') {
            $this->authorizeSecretaryPermission('appointments.manage', $doctor);
        }

        $clinicIds = $this->accessibleClinicIds($doctor);
        $data = $request->validate([
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('doctor_id', $doctor->id)],
            'source_clinic_id' => ['required', 'integer', Rule::exists('addresses', 'id')->where('doctor_id', $doctor->id)->where('is_active', true)],
            'target_provider_id' => ['required', 'integer', Rule::exists('medical_network_providers', 'id')->where('status', 'active')->where('is_verified', true)],
            'service_type' => ['required', Rule::in(['laboratory', 'imaging', 'rehabilitation', 'consultation'])],
            'requested_services' => ['nullable', 'required_if:service_type,laboratory,imaging', 'array', 'min:1', 'max:30'],
            'requested_services.*' => ['required', 'string', 'max:180', 'distinct'],
            'priority' => ['required', Rule::in(['normal', 'urgent'])],
            'reason' => ['required', 'string', 'max:240'],
            'clinical_summary' => ['nullable', 'string', 'max:4000'],
            'patient_message' => ['nullable', 'string', 'max:1000'],
            'consent_confirmed' => ['accepted'],
        ]);

        if (isset($data['requested_services'])) {
            $requestedServices = array_map(
                static fn (string $service): string => trim($service),
                $data['requested_services']
            );
            $requestedServices = array_values(array_unique(array_filter($requestedServices, static fn (string $service): bool => $service !== '')));
            abort_if(
                in_array($data['service_type'], ['laboratory', 'imaging'], true) && $requestedServices === [],
                422,
                'حداقل یک مورد تشخیصی باید ثبت شود.'
            );
            $data['requested_services'] = $requestedServices;
        }

        abort_unless(in_array((int) $data['source_clinic_id'], $clinicIds, true), 403, 'به مطب مبدأ دسترسی ندارید.');
        Patient::query()->whereKey($data['patient_id'])->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->where('clinic_id', $data['source_clinic_id']))->firstOrFail();
        $provider = MedicalNetworkProvider::query()->whereKey($data['target_provider_id'])->where('status', 'active')->where('is_verified', true)->firstOrFail();
        abort_if(
            (int) $provider->created_by_user_id === (int) $user->id || (int) $provider->doctor_id === (int) $doctor->id,
            422,
            'ارجاع به پروفایل یا مرکز متعلق به خودتان امکان‌پذیر نیست.'
        );
        $expectedService = in_array($provider->provider_type, ['doctor', 'psychiatrist', 'psychologist', 'counselor', 'social_worker', 'speech_therapist', 'occupational_therapist', 'nutritionist', 'nurse', 'midwife', 'physiotherapist', 'other'], true) ? 'consultation' : $provider->provider_type;
        abort_unless($data['service_type'] === $expectedService, 422, 'نوع خدمت با نوع مرکز مقصد همخوانی ندارد.');

        $referral = DB::transaction(function () use ($data, $doctor, $user): MedicalReferral {
            $referral = MedicalReferral::query()->create([
                'tracking_code' => strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(6)),
                'patient_id' => $data['patient_id'],
                'referring_doctor_id' => $doctor->id,
                'target_provider_id' => $data['target_provider_id'],
                'source_clinic_id' => $data['source_clinic_id'],
                'created_by_user_id' => $user->id,
                'service_type' => $data['service_type'],
                'requested_services' => $data['requested_services'] ?? null,
                'priority' => $data['priority'],
                'status' => 'sent',
                'reason' => $data['reason'],
                'clinical_summary' => $data['clinical_summary'] ?? null,
                'patient_message' => $data['patient_message'] ?? null,
                'consent_at' => now(),
            ]);
            MedicalReferralEvent::query()->create([
                'medical_referral_id' => $referral->id,
                'user_id' => $user->id,
                'event' => 'created',
                'to_status' => 'sent',
                'description' => 'ارجاع با تأیید رضایت بیمار ثبت شد.',
                'created_at' => now(),
            ]);
            return $referral;
        });

        $audit->handle('medical_network.referral_created', $referral, ['doctor_id' => $doctor->id, 'changed_fields' => ['referral_created']]);
        return back()->with('success', 'ارجاع با کد رهگیری ' . $referral->tracking_code . ' ثبت شد.');
    }

    public function updateReferral(
        Request $request,
        MedicalReferral $referral,
        MedicalReferralWorkflow $workflow,
        RecordSecurityAuditEventAction $audit
    ) {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'scheduled', 'in_progress', 'completed', 'rejected', 'cancelled'])],
            'scheduled_at' => ['nullable', 'date', 'after:now', 'required_if:status,scheduled'],
            'provider_response' => ['nullable', 'string', 'max:2000', 'required_if:status,rejected'],
            'result_summary' => ['nullable', 'string', 'max:4000', 'required_if:status,completed'],
            'patient_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($referral, $data, $user, $workflow): void {
            $locked = MedicalReferral::query()->whereKey($referral->id)->lockForUpdate()->with(['targetProvider', 'referringDoctor'])->firstOrFail();
            $senderSecretary = false;
            if ($user->role === 'secretary' && (int) session('active_doctor_id') === (int) $locked->referring_doctor_id) {
                $assignment = $user->doctors()->where('doctors.id', $locked->referring_doctor_id)->first();
                $permissions = $assignment?->pivot?->permissions ?? [];
                if (is_string($permissions)) {
                    $permissions = json_decode($permissions, true) ?: [];
                }
                $hasClinicAccess = in_array((int) $locked->source_clinic_id, $this->accessibleClinicIds($locked->referringDoctor), true);
                $senderSecretary = $hasClinicAccess && is_array($permissions) && in_array('appointments.manage', $permissions, true);
            }
            $activeDoctorId = (int) session('active_doctor_id');
            $isSender = (
                (int) $locked->referringDoctor?->user_id === (int) $user->id
                && $activeDoctorId === (int) $locked->referring_doctor_id
            ) || $senderSecretary;
            $providerDoctorId = $locked->targetProvider?->doctor_id;
            $isRecipient = (int) $locked->targetProvider?->created_by_user_id === (int) $user->id
                && ($providerDoctorId === null || (int) $providerDoctorId === $activeDoctorId);
            abort_unless($isSender || $isRecipient, 403, 'به این ارجاع دسترسی ندارید.');

            $from = $locked->status;
            $workflow->assertCanTransition($from, $data['status'], $isSender, $isRecipient);
            if ($data['status'] === 'completed') {
                abort_unless($isRecipient && filled($data['result_summary'] ?? null), 422, 'برای تکمیل ارجاع، ثبت نتیجه خدمت الزامی است.');
            }

            $updates = ['status' => $data['status']];
            if ($data['status'] === 'accepted') $updates['accepted_at'] = now();
            if ($data['status'] === 'scheduled') $updates['scheduled_at'] = $data['scheduled_at'];
            if ($data['status'] === 'completed') {
                $updates['completed_at'] = now();
                $updates['result_summary'] = $data['result_summary'];
                $updates['patient_instructions'] = $data['patient_instructions'] ?? null;
            }
            if (array_key_exists('provider_response', $data)) $updates['provider_response'] = $data['provider_response'];
            if ($data['status'] === 'cancelled') $updates['cancelled_at'] = now();
            $locked->update($updates);

            MedicalReferralEvent::query()->create([
                'medical_referral_id' => $locked->id,
                'user_id' => $user->id,
                'event' => 'status_changed',
                'from_status' => $from,
                'to_status' => $data['status'],
                'description' => match ($data['status']) {
                    'accepted' => 'ارجاع پذیرفته شد.',
                    'scheduled' => 'زمان مراجعه تعیین شد.',
                    'in_progress' => 'ارائه خدمت آغاز شد.',
                    'completed' => 'نتیجه خدمت ثبت شد.',
                    'rejected' => 'ارجاع پذیرفته نشد.',
                    'cancelled' => 'ارجاع لغو شد.',
                    default => 'وضعیت ارجاع تغییر کرد.',
                },
                'metadata' => array_filter([
                    'scheduled_at' => $data['scheduled_at'] ?? null,
                    'has_result' => filled($data['result_summary'] ?? null),
                ], fn ($value) => $value !== null),
                'created_at' => now(),
            ]);
            if ($data['status'] === 'completed') {
                $locked->targetProvider()->increment('completed_referrals_count');
            }
        });

        $audit->handle('medical_network.referral_status_changed', $referral, [
            'doctor_id' => (int) session('active_doctor_id'),
            'status_changed' => true,
            'changed_fields' => ['status'],
        ]);
        return back()->with('success', 'وضعیت ارجاع به‌روزرسانی شد.');
    }

    public function review()
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);
        return Inertia::render('Portal/MedicalNetwork/Review', [
            'providers' => MedicalNetworkProvider::query()->where('status', 'pending')
                ->with(['owner:id,name,family,phone', 'organization:id,name'])
                ->latest()->paginate(30),
        ]);
    }

    public function approveProvider(MedicalNetworkProvider $provider, RecordSecurityAuditEventAction $audit)
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);
        abort_unless($provider->status === 'pending', 422);
        $provider->update(['status' => 'active', 'is_verified' => true]);
        $audit->handle('medical_network.provider_approved', $provider, ['changed_fields' => ['status', 'is_verified']]);
        return back()->with('success', 'مرکز پس از بررسی اطلاعات و مجوزها تأیید و در شبکه فعال شد.');
    }

    public function rejectProvider(Request $request, MedicalNetworkProvider $provider, RecordSecurityAuditEventAction $audit)
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);
        abort_unless($provider->status === 'pending', 422);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $provider->update(['status' => 'rejected', 'description' => trim(($provider->description ? $provider->description . "\n\n" : '') . 'نتیجه بررسی: ' . $data['reason'])]);
        $audit->handle('medical_network.provider_rejected', $provider, ['changed_fields' => ['status']]);
        return back()->with('success', 'درخواست عضویت مرکز رد شد.');
    }
}