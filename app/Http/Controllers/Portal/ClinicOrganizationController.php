<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Actions\Portal\Users\SyncOrganizationSecretaryDoctorAccessAction;
use App\Models\ClinicOrganization;
use App\Models\Address;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ClinicOrganizationController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor', 403);

        $organizations = ClinicOrganization::query()
            ->where(function ($q) use ($user): void {
                $q->where('owner_user_id', $user->id)
                    ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id)->where('clinic_organization_members.is_active', true))
                    ->orWhereHas('doctors', fn ($d) => $d
                        ->where('doctors.user_id', $user->id)
                        ->whereIn('clinic_organization_doctors.status', ['pending', 'active']));
            })
            ->with(['doctors', 'clinics', 'members'])
            ->withCount([
                'doctors as doctors_count' => fn ($query) => $query->where('clinic_organization_doctors.status', 'active'),
                'clinics as clinics_count' => fn ($query) => $query->where('clinic_organization_clinics.status', 'active'),
                'members as members_count' => fn ($query) => $query->where('clinic_organization_members.is_active', true),
            ])
            ->latest()->get();

        $availableDoctors = Doctor::query()
            ->where('is_active', true)
            ->whereHas('user', fn ($query) => $query
                ->where('role', 'doctor')
                ->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name', 'family', 'specialty_title']);
        $ownedDoctorIds = $user->ownedDoctorProfiles()
            ->where('is_active', true)
            ->pluck('doctors.id');
        $organizationDoctorIds = $organizations->flatMap(
            fn ($organization) => $organization->doctors->filter(fn ($member) => $member->pivot->status === 'active')->pluck('id')
        );
        $clinicDoctorIds = $ownedDoctorIds->merge($organizationDoctorIds)->unique()->values();

        $clinics = Address::query()
            ->whereIn('doctor_id', $clinicDoctorIds)
            ->whereNull('organization_id')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'doctor_id', 'name', 'full_address']);

        return Inertia::render('Portal/Organizations/Index', [
            'organizations' => $organizations,
            'doctors' => $availableDoctors,
            'clinics' => $clinics,
            'isAdmin' => $user->role === 'admin',
            'currentDoctorId' => $user->doctorProfile()->where('is_active', true)->value('id'),
            'availableStaff' => User::query()->where('role', 'secretary')->where('is_active', true)->orderBy('name')->get(['id','name','family','phone']),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:180'],
            'legal_name' => ['nullable', 'string', 'max:200'],
            'registration_number' => ['nullable', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40'],
            'email' => ['nullable', 'email', 'max:255'],
            'clinic_ids' => ['nullable', 'array'],
            'clinic_ids.*' => ['integer', 'distinct'],
        ]);

        $requestedClinicIds = array_values(array_unique(array_map(
            'intval',
            $data['clinic_ids'] ?? []
        )));

        DB::transaction(function () use ($user, $data, $requestedClinicIds): void {
            User::query()
                ->whereKey($user->id)
                ->where('role', 'doctor')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $ownedDoctor = Doctor::query()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->whereHas('user', fn ($query) => $query
                    ->where('role', 'doctor')
                    ->where('is_active', true))
                ->lockForUpdate()
                ->firstOrFail();

            $clinics = collect();

            if ($requestedClinicIds !== []) {
                $clinics = Address::query()
                    ->where('doctor_id', $ownedDoctor->id)
                    ->whereNull('organization_id')
                    ->where('is_active', true)
                    ->whereIn('id', $requestedClinicIds)
                    ->lockForUpdate()
                    ->get(['id']);

                $clinicIds = $clinics->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (count($clinicIds) !== count($requestedClinicIds)) {
                    throw ValidationException::withMessages([
                        'clinic_ids' => 'یک یا چند شعبه انتخاب‌شده متعلق به شما نیست یا قبلاً به مجموعه دیگری متصل شده است.',
                    ]);
                }

                $alreadyLinkedElsewhere = DB::table('clinic_organization_clinics')
                    ->whereIn('clinic_id', $clinicIds)
                    ->where('status', 'active')
                    ->exists();

                if ($alreadyLinkedElsewhere) {
                    throw ValidationException::withMessages([
                        'clinic_ids' => 'یکی از شعب انتخاب‌شده در ارتباط فعال با یک مجموعه درمانی دیگر قرار دارد.',
                    ]);
                }
            } else {
                $clinicIds = [];
            }

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
                'role' => 'owner',
                'status' => 'active',
                'revenue_share_basis_points' => 7000,
                'joined_at' => today(),
            ]);

            if ($clinicIds !== []) {
                $organization->clinics()->attach($clinicIds, ['status' => 'active']);

                Address::query()
                    ->whereIn('id', $clinicIds)
                    ->update(['organization_id' => $organization->id]);
            }

            $organization->members()->attach($user->id, [
                'role' => 'owner',
                'permissions' => json_encode(['*']),
                'clinic_ids' => json_encode($clinicIds),
                'is_active' => true,
            ]);
        });

        return redirect()
            ->route('portal.organizations.index')
            ->with('success', 'مجموعه درمانی ایجاد شد.');
    }

    public function inviteDoctor(Request $request, ClinicOrganization $organization)
    {
        $this->authorizeOwner($organization);

        $data = $request->validate([
            'doctor_id' => ['required', 'integer', 'exists:doctors,id'],
            'revenue_share_basis_points' => ['required', 'integer', 'min:0', 'max:10000'],
            'role' => ['required', Rule::in(['practitioner', 'clinical_lead', 'manager'])],
        ]);

        DB::transaction(function () use ($organization, $data): void {
            // Serialize invitations within one organization to prevent two
            // concurrent requests from creating conflicting memberships.
            ClinicOrganization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $doctor = Doctor::query()
                ->whereKey($data['doctor_id'])
                ->where('is_active', true)
                ->whereHas('user', fn ($query) => $query
                    ->where('role', 'doctor')
                    ->where('is_active', true))
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                (int) $doctor->user_id === (int) $organization->owner_user_id,
                422,
                'پزشک مالک از قبل عضو مالک مجموعه است.'
            );

            $existingMembership = $organization->doctors()
                ->whereKey($doctor->id)
                ->lockForUpdate()
                ->first();

            abort_if(
                $existingMembership && $existingMembership->pivot->status !== 'inactive',
                409,
                'این پزشک قبلاً به مجموعه اضافه شده است.'
            );

            $membershipData = [
                'role' => $data['role'],
                'status' => 'pending',
                'revenue_share_basis_points' => $data['revenue_share_basis_points'],
                'joined_at' => null,
            ];

            if ($existingMembership) {
                $organization->doctors()->updateExistingPivot($doctor->id, $membershipData);
            } else {
                $organization->doctors()->attach($doctor->id, $membershipData);
            }
        });

        return back()->with('success', 'دعوت پزشک ثبت شد؛ پزشک باید عضویت را تأیید کند.');
    }

    public function acceptDoctor(ClinicOrganization $organization)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user?->role === 'doctor', 403);
        $doctor = $user->doctorProfile()->where('is_active', true)->firstOrFail();

        DB::transaction(function () use ($organization, $doctor): void {
            ClinicOrganization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $membership = DB::table('clinic_organization_doctors')
                ->where('organization_id', $organization->id)
                ->where('doctor_id', $doctor->id)
                ->lockForUpdate()
                ->first();

            abort_unless($membership && $membership->status === 'pending', 404);

            DB::table('clinic_organization_doctors')
                ->where('organization_id', $organization->id)
                ->where('doctor_id', $doctor->id)
                ->update([
                    'status' => 'active',
                    'joined_at' => today(),
                    'updated_at' => now(),
                ]);

            $this->syncOrganizationSecretaryAccess($organization, $doctor);
        });

        return back()->with('success', 'عضویت پزشک در مجموعه تأیید شد.');
    }

    public function attachClinic(Request $request, ClinicOrganization $organization)
    {
        $this->authorizeOwner($organization);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer', 'exists:addresses,id'],
            'branch_code' => ['nullable', 'string', 'max:40'],
        ]);

        DB::transaction(function () use ($organization, $data): void {
            ClinicOrganization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $doctorIds = $organization->doctors()
                ->wherePivot('status', 'active')
                ->pluck('doctors.id');

            $clinic = Address::query()
                ->whereKey($data['clinic_id'])
                ->whereIn('doctor_id', $doctorIds)
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                $clinic->organization_id && (int) $clinic->organization_id !== (int) $organization->id,
                409,
                'این شعبه قبلاً به مجموعه دیگری متصل شده است.'
            );

            $alreadyLinkedElsewhere = DB::table('clinic_organization_clinics')
                ->where('clinic_id', $clinic->id)
                ->where('status', 'active')
                ->where('organization_id', '<>', $organization->id)
                ->exists();

            abort_if(
                $alreadyLinkedElsewhere,
                409,
                'این شعبه در ارتباط فعال با مجموعه درمانی دیگری قرار دارد.'
            );

            $pivot = [
                'branch_code' => $data['branch_code'] ?? null,
                'status' => 'active',
            ];

            $organization->clinics()->syncWithoutDetaching([$clinic->id => $pivot]);
            $organization->clinics()->updateExistingPivot($clinic->id, $pivot);
            $clinic->update(['organization_id' => $organization->id]);

            $this->syncOrganizationSecretaryAccess($organization);
        });

        return back()->with('success', 'شعبه به مجموعه متصل شد.');
    }

    public function detachClinic(ClinicOrganization $organization, int $clinic)
    {
        $this->authorizeOwner($organization);

        DB::transaction(function () use ($organization, $clinic): void {
            ClinicOrganization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $branch = $organization->clinics()
                ->wherePivot('status', 'active')
                ->whereKey($clinic)
                ->lockForUpdate()
                ->firstOrFail();

            $organization->clinics()->updateExistingPivot($branch->id, [
                'status' => 'inactive',
                'is_primary' => false,
            ]);

            Address::query()
                ->whereKey($branch->id)
                ->where('organization_id', $organization->id)
                ->update(['organization_id' => null]);

            $this->removeClinicIdsFromOrganizationMembers($organization, [$branch->id]);
            $this->syncOrganizationSecretaryAccess($organization);
        });

        return back()->with('success', 'اتصال شعبه به مجموعه قطع شد؛ سوابق شعبه حذف نشده‌اند.');
    }

    public function removeDoctor(ClinicOrganization $organization, int $doctor)
    {
        $this->authorizeOwner($organization);

        DB::transaction(function () use ($organization, $doctor): void {
            ClinicOrganization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $doctorProfile = Doctor::query()->whereKey($doctor)->firstOrFail();

            $membership = DB::table('clinic_organization_doctors')
                ->where('organization_id', $organization->id)
                ->where('doctor_id', $doctorProfile->id)
                ->lockForUpdate()
                ->first();

            abort_unless($membership, 404);
            abort_if(
                $membership->role === 'owner',
                422,
                'پزشک مالک مجموعه را نمی‌توان از عضویت مالک خارج کرد.'
            );

            $clinicIds = $organization->clinics()
                ->wherePivot('status', 'active')
                ->where('addresses.doctor_id', $doctorProfile->id)
                ->pluck('addresses.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            DB::table('clinic_organization_doctors')
                ->where('organization_id', $organization->id)
                ->where('doctor_id', $doctorProfile->id)
                ->update([
                    'status' => 'inactive',
                    'updated_at' => now(),
                ]);

            if ($clinicIds !== []) {
                DB::table('clinic_organization_clinics')
                    ->where('organization_id', $organization->id)
                    ->whereIn('clinic_id', $clinicIds)
                    ->update([
                        'status' => 'inactive',
                        'updated_at' => now(),
                    ]);

                Address::query()
                    ->whereIn('id', $clinicIds)
                    ->where('organization_id', $organization->id)
                    ->update(['organization_id' => null]);

                $this->removeClinicIdsFromOrganizationMembers($organization, $clinicIds);
            }

            // Preserve direct and other-organization grants; remove only this
            // organization's contribution for the removed doctor.
            $this->syncOrganizationSecretaryAccess($organization, $doctorProfile);
        });

        return back()->with('success', 'پزشک از مجموعه خارج شد؛ سوابق و شعبه‌های فیزیکی حذف نشده‌اند.');
    }

    public function inviteMember(Request $request, ClinicOrganization $organization)
    {
        $this->authorizeOwner($organization);

        $allowed = [
            'appointments.view','appointments.manage','patients.view','patients.manage',
            'patients.clinical.view','patients.clinical.manage','clinics.view',
            'shifts.view','shifts.manage','working-hours.view','working-hours.manage',
            'finance.view','finance.manage','services.view','services.manage',
            'inventory.view','inventory.manage','staff.attendance.view',
        ];

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'role' => [Rule::in(['receptionist','accountant','clinic_manager','inventory_manager','secretary'])],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => [Rule::in($allowed)],
            'clinic_ids' => ['required', 'array', 'min:1'],
            'clinic_ids.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($organization, $data): void {
            ClinicOrganization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $member = User::query()
                ->whereKey($data['user_id'])
                ->where('role', 'secretary')
                ->where('is_active', true)
                ->lockForUpdate()
                ->firstOrFail();

            $clinicIds = $organization->clinics()
                ->wherePivot('status', 'active')
                ->where('addresses.is_active', true)
                ->where('addresses.organization_id', $organization->id)
                ->whereIn('addresses.id', $data['clinic_ids'])
                ->lockForUpdate()
                ->pluck('addresses.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (count($clinicIds) !== count($data['clinic_ids'])) {
                throw ValidationException::withMessages([
                    'clinic_ids' => 'یکی از شعب انتخاب‌شده متعلق به این مجموعه یا فعال نیست.',
                ]);
            }

            $organization->members()->syncWithoutDetaching([
                $member->id => [
                    'role' => $data['role'] ?? 'secretary',
                    'permissions' => json_encode(array_values(array_unique($data['permissions']))),
                    'clinic_ids' => json_encode($clinicIds),
                    'is_active' => true,
                ],
            ]);

            $this->syncOrganizationSecretaryAccess($organization);
        });

        return back()->with('success', 'کارمند به مجموعه و شعبه‌های انتخاب‌شده متصل شد.');
    }

    public function removeMember(ClinicOrganization $organization, int $member)
    {
        $this->authorizeOwner($organization);

        DB::transaction(function () use ($organization, $member): void {
            ClinicOrganization::query()
                ->whereKey($organization->id)
                ->lockForUpdate()
                ->firstOrFail();

            $user = $organization->members()
                ->whereKey($member)
                ->lockForUpdate()
                ->firstOrFail();

            abort_if(
                (int) $user->id === (int) $organization->owner_user_id,
                422,
                'مالک مجموعه را نمی‌توان از عضویت مالک خارج کرد.'
            );

            $doctors = $organization->doctors()
                ->wherePivot('status', 'active')
                ->lockForUpdate()
                ->get();

            $organization->members()->detach($user->id);

            // Recalculate the removed member against every remaining organization
            // before syncing the rest of this organization's active staff.
            foreach ($doctors as $doctor) {
                app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($user, $doctor);
            }

            $this->syncOrganizationSecretaryAccess($organization);
        });

        return back()->with('success', 'دسترسی کارمند به مجموعه لغو شد.');
    }

    private function syncOrganizationSecretaryAccess(
        ClinicOrganization $organization,
        ?Doctor $onlyDoctor = null
    ): void {
        $doctors = $onlyDoctor
            ? collect([$onlyDoctor])
            : $organization->doctors()->wherePivot('status', 'active')->get();

        $members = $organization->members()
            ->where('users.role', 'secretary')
            ->where('users.is_active', true)
            ->wherePivot('is_active', true)
            ->get();

        foreach ($doctors as $doctor) {
            foreach ($members as $member) {
                app(SyncOrganizationSecretaryDoctorAccessAction::class)->handle($member, $doctor);
            }
        }
    }

    private function removeClinicIdsFromOrganizationMembers(
        ClinicOrganization $organization,
        array $clinicIds
    ): void {
        $clinicIds = array_map('intval', $clinicIds);

        foreach (DB::table('clinic_organization_members')
            ->where('organization_id', $organization->id)
            ->get(['user_id', 'clinic_ids']) as $member) {
            $currentIds = array_map(
                'intval',
                $this->decodeJsonArray($member->clinic_ids)
            );
            $remainingIds = array_values(array_diff($currentIds, $clinicIds));

            DB::table('clinic_organization_members')
                ->where('organization_id', $organization->id)
                ->where('user_id', $member->user_id)
                ->update([
                    'clinic_ids' => json_encode($remainingIds),
                    'updated_at' => now(),
                ]);
        }
    }

    private function decodeJsonArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function authorizeOwner(ClinicOrganization $organization): void
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor' && (int) $organization->owner_user_id === (int) $user->id, 403);
    }
}
