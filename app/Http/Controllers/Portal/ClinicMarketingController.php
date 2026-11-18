<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicMarketingController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        $permissions = $this->secretaryPermissions($doctor);
        $canView = $user->role === 'doctor'
            || in_array('marketing.view', $permissions, true)
            || in_array('marketing.manage', $permissions, true);
        abort_unless($canView, 403);

        $clinicIds = $this->accessibleClinicIds($doctor);
        $campaigns = DB::table('clinic_marketing_campaigns as c')
            ->leftJoin('addresses as a', 'a.id', '=', 'c.clinic_id')
            ->where('c.doctor_id', $doctor->id)
            ->where(function ($query) use ($clinicIds) {
                $query->whereNull('c.clinic_id')->orWhereIn('c.clinic_id', $clinicIds);
            })
            ->select('c.*', 'a.name as clinic_name')
            ->selectSub(DB::table('clinic_marketing_events')->selectRaw('COUNT(*)')
                ->whereColumn('campaign_id', 'c.id')->where('event_type', 'click'), 'clicks')
            ->selectSub(DB::table('clinic_marketing_events')->selectRaw('COUNT(*)')
                ->whereColumn('campaign_id', 'c.id')->where('event_type', 'booking'), 'bookings')
            ->latest('c.created_at')->limit(100)->get()
            ->map(function ($campaign) {
                $campaign->share_url = route('growth.track', ['code' => $campaign->code]);
                $campaign->conversion_rate = (int) $campaign->clicks > 0
                    ? round(((int) $campaign->bookings / (int) $campaign->clicks) * 100, 1) : 0;
                return $campaign;
            });

        $totals = [
            'campaigns' => $campaigns->count(),
            'active' => $campaigns->where('status', 'active')->count(),
            'clicks' => (int) $campaigns->sum('clicks'),
            'bookings' => (int) $campaigns->sum('bookings'),
        ];

        return Inertia::render('Portal/Marketing/Index', [
            'doctor' => $doctor,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->get(['id', 'name']),
            'campaigns' => $campaigns,
            'totals' => $totals,
            'canManage' => $user->role === 'doctor' || in_array('marketing.manage', $permissions, true),
        ]);
    }

    public function store(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeMarketingManagement($doctor);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'clinic_id' => ['nullable', 'integer'],
            'channel' => ['required', Rule::in(['social', 'sms', 'poster', 'website', 'partner', 'other'])],
            'budget_rial' => ['nullable', 'integer', 'min:0', 'max:1000000000000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ]);

        if (!empty($data['clinic_id'])) {
            $this->authorizeClinicAccess((int) $data['clinic_id'], $doctor);
        }

        do {
            $code = Str::upper(Str::random(8));
        } while (DB::table('clinic_marketing_campaigns')->where('code', $code)->exists());

        DB::table('clinic_marketing_campaigns')->insert([
            'doctor_id' => $doctor->id,
            'clinic_id' => $data['clinic_id'] ?? null,
            'created_by' => Auth::guard('web')->id(),
            'name' => $data['name'],
            'code' => $code,
            'channel' => $data['channel'],
            'status' => 'active',
            'budget_rial' => $data['budget_rial'] ?? null,
            'starts_at' => $data['starts_at'] ?? null,
            'ends_at' => $data['ends_at'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'کمپین ساخته شد؛ لینک اختصاصی آماده اشتراک‌گذاری است.');
    }

    public function toggle(int $campaign)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeMarketingManagement($doctor);
        $row = DB::table('clinic_marketing_campaigns')->where('id', $campaign)
            ->where('doctor_id', $doctor->id)->first();
        abort_unless($row, 404);
        $status = $row->status === 'active' ? 'paused' : 'active';
        DB::table('clinic_marketing_campaigns')->where('id', $campaign)->update([
            'status' => $status, 'updated_at' => now(),
        ]);

        return back()->with('success', $status === 'active' ? 'کمپین فعال شد.' : 'کمپین متوقف شد.');
    }

    public function track(string $code)
    {
        $campaign = DB::table('clinic_marketing_campaigns as c')
            ->join('doctors as d', 'd.id', '=', 'c.doctor_id')
            ->where('c.code', strtoupper($code))
            ->where('c.status', 'active')
            ->where('d.is_active', true)
            ->where(function ($query) {
                $query->whereNull('c.starts_at')->orWhere('c.starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('c.ends_at')->orWhere('c.ends_at', '>=', now());
            })
            ->select('c.id', 'c.doctor_id', 'd.slug')
            ->first();

        abort_unless($campaign, 404);
        DB::table('clinic_marketing_events')->insert([
            'campaign_id' => $campaign->id,
            'event_type' => 'click',
            'occurred_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        session([
            'proneex_marketing_campaign_id' => (int) $campaign->id,
            'proneex_marketing_doctor_id' => (int) $campaign->doctor_id,
            'proneex_marketing_expires_at' => now()->addDays(30)->timestamp,
        ]);

        return redirect()->route('home1', ['doctor' => $campaign->slug]);
    }

    private function authorizeMarketingManagement($doctor): void
    {
        $user = Auth::guard('web')->user();
        if ($user?->role === 'doctor') {
            return;
        }
        $this->authorizeSecretaryPermission('marketing.manage', $doctor);
    }

    private function secretaryPermissions($doctor): array
    {
        $user = Auth::guard('web')->user();
        if ($user?->role !== 'secretary') return [];
        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) $permissions = json_decode($permissions, true) ?: [];
        return is_array($permissions) ? $permissions : [];
    }
}
