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
                    ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id)->wherePivot('is_active', true))
                    ->orWhereHas('doctors', fn ($d) => $d->where('doctors.user_id', $user->id)->wherePivot('status', 'pending'));
            })
            ->with(['doctors' => fn ($q) => $q->withPivot(['role', 'status', 'revenue_share_basis_points']), 'clinics'])
            ->withCount(['doctors', 'clinics', 'members'])
            ->latest()->get();

        $ownedDoctors = Doctor::query()->where('is_active', true)->orderBy('name')
            ->get(['id', 'name', 'family', 'specialty_title']);

        $clinics = \App\\Models\\Address::query()
            ->where('is_active', true)->orderBy('name')
            ->get(['id', 'doctor_id', 'name', 'full_address']);

        return Inertia::render('Portal/Organizations/Index', [
            'organizations' => $organizations,
            'doctors' => $ownedDoctors,
            'clinics' => $clinics,
            'isAdmin' => $user->role === 'admin',
            'currentDoctorId' => $user->doctorProfile()->value('id'),
            'availableStaff' => User::query()->where('role', 'secretary')->where('is_active', true)->orderBy('name')->get(['id','name','family','phone']),
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
            'doctor_id' => [Rule::requiredIf($user->role === 'admin'), 'nullable', 'integer', 'exists:doctors,id'],
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

    public function inviteMember(Request $request, ClinicOrganization $organization)
    {
        $this->authorizeOwner($organization);
        $allowed = [
            'appointments.view','appointments.manage','patients.view','patients.manage',
            'patients.clinical.view','patients.clinical.manage','clinics.view',
            'shifts.view','shifts.manage','working-hours.view','working-hours.manage',
            'finance.view','finance.manage','services.view','services.manage',
            'inventory.view','inventory.manage',
        ];
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => [Rule::in(['receptionist','accountant','clinic_manager','inventory_manager','secretary'])],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [Rule::in($allowed)],
            'clinic_ids' => ['required', 'array', 'min:1'],
            'clinic_ids.*' => ['integer', 'distinct'],
        ]);
        $member = User::query()->whereKey($data['user_id'])->where('role', 'secretary')->where('is_active', true)->firstOrFail();
        $clinicIds = $organization->clinics()->whereIn('addresses.id', $data['clinic_ids'])->pluck('addresses.id')->map(fn ($id) => (int) $id)->all();
        abort_unless(count($clinicIds) === count($data['clinic_ids']), 422, 'یکی از شعب انتخاب‌شده متعلق به این مجموعه نیست.');

        DB::transaction(function () use ($organization, $member, $data, $clinicIds): void {
            $organization->members()->syncWithoutDetaching([
                $member->id => [
                    'role' => $data['role'] ?? 'secretary',
                    'permissions' => json_encode(array_values(array_unique($data['permissions']))),
                    'clinic_ids' => json_encode($clinicIds),
                    'is_active' => true,
                ],
            ]);
            $doctors = $organization->doctors()->wherePivot('status', 'active')->get();
            foreach ($doctors as $doctor) {
                $doctorClinicIds = $organization->clinics()->where('addresses.doctor_id', $doctor->id)
                    ->whereIn('addresses.id', $clinicIds)->pluck('addresses.id')->all();
                if (!$doctorClinicIds) continue;
                $doctor->secretaries()->syncWithoutDetaching([
                    $member->id => [
                        'permissions' => json_encode(array_values(array_unique($data['permissions']))),
                        'clinic_ids' => json_encode($doctorClinicIds),
                    ],
                ]);
            }
        });
        return back()->with('success', 'کارمند به مجموعه و شعبه‌های انتخاب‌شده متصل شد.');
    }

    public function removeMember(ClinicOrganization $organization, int $member)
    {
        $this->authorizeOwner($organization);
        $user = $organization->members()->whereKey($member)->firstOrFail();
        DB::transaction(function () use ($organization, $user): void {
            foreach ($organization->doctors()->get() as $doctor) {
                $doctor->secretaries()->detach($user->id);
            }
            $organization->members()->detach($user->id);
        });
        return back()->with('success', 'دسترسی کارمند به مجموعه لغو شد.');
    }

    private function authorizeOwner(ClinicOrganization $organization): void
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && ($user->role === 'admin' || $organization->owner_user_id === $user->id), 403);
    }
}
