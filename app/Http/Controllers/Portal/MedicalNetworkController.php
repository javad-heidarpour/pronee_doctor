<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
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

    public function index(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary', 'admin'], true), 403);
        $doctor = $this->currentDoctor();
        if ($user->role === 'secretary') {
            $this->authorizeSecretaryPermission('appointments.view', $doctor);
        }

        $providers = MedicalNetworkProvider::query()
            ->where('status', 'active')
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
            ->orderByDesc('is_verified')->orderByDesc('rating')->orderBy('name')
            ->paginate(12)->withQueryString();

        $outgoing = MedicalReferral::query()
            ->where('referring_doctor_id', $doctor->id)
            ->with(['patient:id,name,phone', 'targetProvider:id,name,provider_type,city'])
            ->latest()->limit(40)->get();

        $incoming = MedicalReferral::query()
            ->whereHas('targetProvider', fn ($q) => $q->where('created_by_user_id', $user->id))
            ->with(['patient:id,name', 'referringDoctor:id,name,family', 'targetProvider:id,name,provider_type'])
            ->latest()->limit(40)->get();

        return Inertia::render('Portal/MedicalNetwork/Index', [
            'providers' => $providers,
            'outgoing' => $outgoing,
            'incoming' => $incoming,
            'patients' => Patient::query()->where('doctor_id', $doctor->id)->orderBy('name')->get(['id', 'name', 'phone']),
            'clinics' => Address::query()->whereIn('id', $this->accessibleClinicIds($doctor))->get(['id', 'name']),
            'filters' => $request->only(['type', 'city', 'search']),
            'providerTypes' => [
                ['value' => 'doctor', 'label' => 'پزشک'],
                ['value' => 'laboratory', 'label' => 'آزمایشگاه'],
                ['value' => 'imaging', 'label' => 'تصویربرداری'],
                ['value' => 'rehabilitation', 'label' => 'توان‌بخشی'],
            ],
        ]);
    }

    public function storeProvider(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor', 403);
        $doctor = $this->currentDoctor();

        $data = $request->validate([
            'provider_type' => ['required', Rule::in(['doctor', 'laboratory', 'imaging', 'rehabilitation'])],
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

        return back()->with('success', 'مرکز برای بررسی و تأیید در شبکه ثبت شد. پس از تأیید، در جست‌وجوی همکاران نمایش داده می‌شود.');
    }

    public function storeReferral(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'secretary'], true), 403);
        $doctor = $this->currentDoctor();
        if ($user->role === 'secretary') {
            $this->authorizeSecretaryPermission('appointments.manage', $doctor);
        }

        $data = $request->validate([
            'patient_id' => ['required', 'integer', Rule::exists('patients', 'id')->where('doctor_id', $doctor->id)],
            'target_provider_id' => ['required', 'integer', Rule::exists('medical_network_providers', 'id')->where('status', 'active')],
            'service_type' => ['required', Rule::in(['laboratory', 'imaging', 'rehabilitation', 'consultation'])],
            'priority' => ['required', Rule::in(['normal', 'urgent'])],
            'reason' => ['required', 'string', 'max:240'],
            'clinical_summary' => ['nullable', 'string', 'max:4000'],
            'patient_message' => ['nullable', 'string', 'max:1000'],
            'consent_confirmed' => ['accepted'],
        ]);

        $provider = MedicalNetworkProvider::query()->whereKey($data['target_provider_id'])->where('status', 'active')->firstOrFail();
        abort_if($provider->doctor_id === $doctor->id, 422, 'ارجاع به پروفایل خودتان امکان‌پذیر نیست.');

        $referral = DB::transaction(function () use ($data, $doctor, $user): MedicalReferral {
            $referral = MedicalReferral::query()->create([
                'tracking_code' => strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(6)),
                'patient_id' => $data['patient_id'],
                'referring_doctor_id' => $doctor->id,
                'target_provider_id' => $data['target_provider_id'],
                'created_by_user_id' => $user->id,
                'service_type' => $data['service_type'],
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

        return back()->with('success', 'ارجاع با کد رهگیری ' . $referral->tracking_code . ' ثبت شد.');
    }

    public function updateReferral(Request $request, MedicalReferral $referral)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $referral->loadMissing('targetProvider');
        $isSender = (int) $referral->referring_doctor_id === (int) $this->currentDoctor()->id;
        $isRecipient = (int) $referral->targetProvider->created_by_user_id === (int) $user->id;
        abort_unless($isSender || $isRecipient, 403);

        $data = $request->validate([
            'status' => ['required', Rule::in(['accepted', 'in_progress', 'completed', 'rejected', 'cancelled'])],
            'provider_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $from = $referral->status;
        $allowed = [
            'sent' => ['accepted', 'rejected', 'cancelled'],
            'accepted' => ['in_progress', 'completed', 'rejected', 'cancelled'],
            'in_progress' => ['completed', 'rejected', 'cancelled'],
        ];
        abort_unless(in_array($data['status'], $allowed[$from] ?? [], true), 422, 'تغییر وضعیت انتخاب‌شده مجاز نیست.');
        abort_unless(!$isSender || in_array($data['status'], ['cancelled'], true), 403, 'ارجاع‌دهنده فقط می‌تواند ارجاع را لغو کند.');
        abort_unless(!$isRecipient || $data['status'] !== 'cancelled', 403, 'مرکز مقصد نمی‌تواند ارجاع را به نام پزشک لغو کند.');

        DB::transaction(function () use ($referral, $data, $user, $from): void {
            $updates = ['status' => $data['status']];
            if ($data['status'] === 'accepted') $updates['accepted_at'] = now();
            if ($data['status'] === 'completed') $updates['completed_at'] = now();
            if (array_key_exists('provider_response', $data)) $updates['provider_response'] = $data['provider_response'];
            if ($data['status'] === 'cancelled') $updates['cancelled_at'] = now();
            $referral->update($updates);
            MedicalReferralEvent::query()->create([
                'medical_referral_id' => $referral->id,
                'user_id' => $user->id,
                'event' => 'status_changed',
                'from_status' => $from,
                'to_status' => $data['status'],
                'description' => $data['provider_response'] ?? null,
                'created_at' => now(),
            ]);
            if ($data['status'] === 'completed') {
                $referral->targetProvider()->increment('completed_referrals_count');
            }
        });

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

    public function approveProvider(MedicalNetworkProvider $provider)
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);
        abort_unless($provider->status === 'pending', 422);
        $provider->update(['status' => 'active', 'is_verified' => true]);
        return back()->with('success', 'مرکز پس از بررسی اطلاعات و مجوزها تأیید و در شبکه فعال شد.');
    }

    public function rejectProvider(Request $request, MedicalNetworkProvider $provider)
    {
        abort_unless(Auth::guard('web')->user()?->role === 'admin', 403);
        abort_unless($provider->status === 'pending', 422);
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']]);
        $provider->update(['status' => 'rejected', 'description' => trim(($provider->description ? $provider->description . "\\n\\n" : '') . 'نتیجه بررسی: ' . $data['reason'])]);
        return back()->with('success', 'درخواست عضویت مرکز رد شد.');
    }
}