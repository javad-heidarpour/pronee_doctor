<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\ClinicOrganization;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClinicOrganizationController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'admin'], true), 403);

        $organizations = ClinicOrganization::query()
            ->where(function ($q) use ($user): void {
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id)->wherePivot('is_active', true));
            })
            ->with(['doctors' => fn ($q) => $q->withPivot(['role', 'status', 'revenue_share_basis_points']), 'clinics'])
            ->withCount(['doctors', 'clinics', 'members'])
            ->latest()->get();

        $ownedDoctors = $user->role === 'admin'
            ? Doctor::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'family', 'specialty_title'])
            : $user->ownedDoctorProfiles()->where('is_active', true)->get(['id', 'name', 'family', 'specialty_title']);

        $ownedClinicIds = $user->role === 'admin'
            ? \App\Models\Address::query()->where('is_active', true)->pluck('id')
            : \App\Models\Address::query()->whereIn('doctor_id', $ownedDoctors->pluck('id'))->where('is_active', true)->pluck('id');

        $clinics = \App\Models\Address::query()->whereIn('id', $ownedClinicIds)->get(['id', 'doctor_id', 'name', 'full_address']);

        return Inertia::render('Portal/Organizations/Index', [
            'organizations' => $organizations,
            'doctors' => $ownedDoctors,
            'clinics' => $clinics,
            'isAdmin' => $user->role === 'admin',
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'admin'], true), 403);
        $ownedDoctor = $user->role === 'admin'
            ? Doctor::query()->whereKey($request->input('doctor_id'))->firstOrFail()
            : $user->ownedDoctorProfiles()->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'clinic_ids' => ['nullable', 'array'],
            'clinic_ids.*' => ['integer', 'distinct'],
        ]);
        $clinicIds = \App\Models\Address::query()->where('doctor_id', $ownedDoctor->id)
            ->whereIn('id', $data['clinic_ids'] ?? [])->pluck('id')->all();

        $organization = DB::transaction(function () use ($user, $ownedDoctor, $data, $clinicIds): ClinicOrganization {
            $organization = ClinicOrganization::create([
                'owner_user_id' => $user->id,
                'name' => $data['name'],
                'slug' => Str::slug($data['name']) . '-' . Str::lower(Str::random(6)),
                'legal_name' => $data['legal_name'] ?? null,
                'registration_number' => $data['registration_number'] ?? null,
                'phone' => $data['phone'] ?? null,
                'email' => $data['email'] ?? null,
            ]);
            $organization->doctors()->attach($ownedDoctor->id, [
                'role' => 'owner', 'status' => 'active', 'revenue_share_basis_points' => 7000, 'joined_at' => today(),
            ]);
            if ($clinicIds) {
                $organization->clinics()->attach($clinicIds, ['status' => 'active']);
                \App\Models\Address::whereIn('id', $clinicIds)->update(['organization_id' => $organization->id]);
            }
            $organization->members()->attach($user->id, [
                'role' => 'owner', 'permissions' => json_encode(['*']), 'clinic_ids' => json_encode($clinicIds), 'is_active' => true,
            ]);
            return $organization;
        });

        return redirect()->route('portal.organizations.index')->with('success', 'مجموعه درمانی ایجاد شد.');
    }

    public function inviteDoctor(Request $request, ClinicOrganization $organization)
    {
        $this->authorizeOwner($organization);
        $data = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'revenue_share_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'role' => ['required', Rule::in(['practitioner', 'clinical_lead', 'manager'])],
        ]);
        $doctor = Doctor::query()->whereKey($data['doctor_id'])->where('is_active', true)->firstOrFail();
        abort_if($organization->doctors()->whereKey($doctor->id)->exists(), 409, 'این پزشک قبلاً به مجموعه اضافه شده است.');
        $organization->doctors()->attach($doctor->id, [
            'role' => $data['role'], 'status' => 'pending',
            'revenue_share_basis_points' => $data['revenue_share_basis_points'],
        ]);
        return back()->with('success', 'دعوت پزشک ثبت شد؛ پزشک باید عضویت را تأیید کند.');
    }

    public function acceptDoctor(ClinicOrganization $organization)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user?->role === 'doctor', 403);
        $doctor = $user->doctorProfile()->firstOrFail();
        $membership = $organization->doctors()->whereKey($doctor->id)->first();
        abort_unless($membership && $membership->pivot->status === 'pending', 404);
        $organization->doctors()->updateExistingPivot($doctor->id, ['status' => 'active', 'joined_at' => today()]);
        return back()->with('success', 'عضویت پزشک در مجموعه تأیید شد.');
    }

    public function attachClinic(Request $request, ClinicOrganization $organization)
    {
        $this->authorizeOwner($organization);
        $data = $request->validate(['clinic_id' => ['required', 'integer', 'exists:addresses,id'], 'branch_code' => ['nullable', 'string', 'max:40']]);
        $doctorIds = $organization->doctors()->wherePivot('status', 'active')->pluck('doctors.id');
        $clinic = \App\Models\Address::query()->whereKey($data['clinic_id'])->whereIn('doctor_id', $doctorIds)->firstOrFail();
        $organization->clinics()->syncWithoutDetaching([$clinic->id => ['branch_code' => $data['branch_code'] ?? null, 'status' => 'active']]);
        $clinic->update(['organization_id' => $organization->id]);
        return back()->with('success', 'شعبه به مجموعه متصل شد.');
    }

    private function authorizeOwner(ClinicOrganization $organization): void
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && ($user->role === 'admin' || $organization->owner_user_id === $user->id), 403);
    }
}
