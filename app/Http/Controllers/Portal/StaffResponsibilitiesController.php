<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class StaffResponsibilitiesController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        $this->authorizeSecretaryPermission('staff.attendance.view', $doctor);

        $organizations = $doctor->clinicOrganizations()
            ->wherePivot('status', 'active')
            ->get()
            ->filter(function ($organization) use ($user): bool {
                return (int) $organization->owner_user_id === (int) $user->id
                    || DB::table('clinic_organization_members')
                        ->where('organization_id', $organization->id)
                        ->where('user_id', $user->id)
                        ->where('is_active', true)
                        ->exists();
            })
            ->values();

        abort_if($organizations->isEmpty(), 403, 'عضویت فعالی در مجموعه درمانی یافت نشد.');

        $scopes = [];
        foreach ($organizations as $organization) {
            $isOwner = (int) $organization->owner_user_id === (int) $user->id;
            $membership = $isOwner ? null : DB::table('clinic_organization_members')
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->first();

            $clinicIds = DB::table('clinic_organization_clinics')
                ->where('organization_id', $organization->id)
                ->where('status', 'active')
                ->pluck('clinic_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            if (!$isOwner) {
                $assigned = $membership?->clinic_ids;
                $assigned = is_string($assigned) ? (json_decode($assigned, true) ?: []) : (array) $assigned;
                $clinicIds = array_values(array_intersect($clinicIds, array_map('intval', $assigned)));
            }

            if ($clinicIds) {
                $scopes[] = [
                    'organization_id' => (int) $organization->id,
                    'name' => $organization->name,
                    'clinic_ids' => $clinicIds,
                    'can_manage' => $isOwner,
                ];
            }
        }

        abort_if(!$scopes, 403, 'به هیچ شعبه فعالی دسترسی ندارید.');

        $rows = DB::table('clinic_staff_responsibilities as r')
            ->join('users as u', 'u.id', '=', 'r.staff_user_id')
            ->join('addresses as c', 'c.id', '=', 'r.clinic_id')
            ->whereNull('r.deleted_at')
            ->where(function ($query) use ($scopes, $user): void {
                foreach ($scopes as $scope) {
                    $query->orWhere(function ($nested) use ($scope, $user): void {
                        $nested->where('r.organization_id', $scope['organization_id'])
                            ->whereIn('r.clinic_id', $scope['clinic_ids']);
                        if (!$scope['can_manage']) {
                            $nested->where('r.staff_user_id', $user->id);
                        }
                    });
                }
            })
            ->orderByRaw("CASE r.status WHEN 'active' THEN 0 WHEN 'paused' THEN 1 ELSE 2 END")
            ->orderBy('r.effective_from')
            ->get([
                'r.id', 'r.organization_id', 'r.clinic_id', 'r.staff_user_id',
                'r.title', 'r.description', 'r.priority', 'r.status',
                'r.effective_from', 'r.effective_until', 'r.review_at',
                'r.created_at', 'r.updated_at', 'u.name as staff_name',
                'u.family as staff_family', 'c.name as clinic_name',
            ])
            ->map(function ($row) use ($scopes): object {
                $scope = collect($scopes)->first(fn ($item) => $item['organization_id'] === (int) $row->organization_id);
                $row->can_manage = (bool) ($scope['can_manage'] ?? false);
                return $row;
            });

        $history = DB::table('clinic_staff_responsibility_events as e')
            ->leftJoin('users as actor', 'actor.id', '=', 'e.actor_user_id')
            ->whereIn('e.responsibility_id', $rows->pluck('id'))
            ->orderByDesc('e.created_at')
            ->get([
                'e.responsibility_id', 'e.event_type', 'e.old_values', 'e.new_values',
                'e.reason', 'e.created_at', 'actor.name as actor_name', 'actor.family as actor_family',
            ])
            ->groupBy('responsibility_id');

        $rows->each(function ($row) use ($history): void {
            $row->history = $history->get($row->id, collect())->values();
        });

        $members = collect();
        $clinics = [];
        foreach ($scopes as $scope) {
            $clinics = array_merge($clinics, DB::table('clinic_organization_clinics as oc')
                ->join('addresses as a', 'a.id', '=', 'oc.clinic_id')
                ->where('oc.organization_id', $scope['organization_id'])
                ->where('oc.status', 'active')
                ->whereIn('oc.clinic_id', $scope['clinic_ids'])
                ->get(['oc.organization_id', 'a.id', 'a.name'])
                ->map(fn ($clinic) => (array) $clinic)->all());

            if ($scope['can_manage']) {
                $members = $members->merge(DB::table('clinic_organization_members as m')
                    ->join('users as u', 'u.id', '=', 'm.user_id')
                    ->where('m.organization_id', $scope['organization_id'])
                    ->where('m.is_active', true)
                    ->get(['m.organization_id', 'm.user_id', 'm.clinic_ids', 'u.name', 'u.family'])
                    ->map(function ($member) use ($scope): object {
                        $assigned = is_string($member->clinic_ids) ? (json_decode($member->clinic_ids, true) ?: []) : (array) $member->clinic_ids;
                        $member->clinic_ids = array_values(array_intersect($scope['clinic_ids'], array_map('intval', $assigned)));
                        return $member;
                    })
                    ->filter(fn ($member) => count($member->clinic_ids) > 0));
            }
        }

        return Inertia::render('Portal/StaffResponsibilities/Index', [
            'doctor' => $doctor,
            'organizations' => $scopes,
            'clinics' => $clinics,
            'members' => $members->values(),
            'responsibilities' => $rows,
            'currentUserId' => (int) $user->id,
        ]);
    }

    public function store(Request $request)
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        $this->authorizeSecretaryPermission('staff.attendance.view', $doctor);
        $data = $this->validated($request);

        $organization = $doctor->clinicOrganizations()
            ->wherePivot('status', 'active')
            ->where('owner_user_id', $user->id)
            ->whereKey($data['organization_id'])
            ->firstOrFail();

        $this->assertAssignment($organization->id, (int) $data['clinic_id'], (int) $data['staff_user_id']);

        DB::transaction(function () use ($data, $user, $organization): void {
            $id = DB::table('clinic_staff_responsibilities')->insertGetId([
                'organization_id' => $organization->id,
                'clinic_id' => $data['clinic_id'],
                'staff_user_id' => $data['staff_user_id'],
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'priority' => $data['priority'],
                'status' => $data['status'],
                'effective_from' => $data['effective_from'],
                'effective_until' => $data['effective_until'] ?: null,
                'review_at' => $data['review_at'] ?: null,
                'created_by' => $user->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('clinic_staff_responsibility_events')->insert([
                'responsibility_id' => $id,
                'actor_user_id' => $user->id,
                'event_type' => 'created',
                'new_values' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'شرح وظایف و مسئولیت جدید ثبت شد.');
    }

    public function update(Request $request, int $responsibility)
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        $this->authorizeSecretaryPermission('staff.attendance.view', $doctor);
        $data = $this->validated($request);

        $organization = $doctor->clinicOrganizations()
            ->wherePivot('status', 'active')
            ->where('owner_user_id', $user->id)
            ->whereKey($data['organization_id'])
            ->firstOrFail();

        $this->assertAssignment($organization->id, (int) $data['clinic_id'], (int) $data['staff_user_id']);

        DB::transaction(function () use ($data, $user, $organization, $responsibility): void {
            $record = DB::table('clinic_staff_responsibilities')
                ->where('id', $responsibility)
                ->where('organization_id', $organization->id)
                ->whereNull('deleted_at')
                ->lockForUpdate()
                ->first();
            abort_unless($record, 404);

            $old = (array) $record;
            DB::table('clinic_staff_responsibilities')->where('id', $responsibility)->update([
                'clinic_id' => $data['clinic_id'],
                'staff_user_id' => $data['staff_user_id'],
                'title' => trim($data['title']),
                'description' => trim($data['description']),
                'priority' => $data['priority'],
                'status' => $data['status'],
                'effective_from' => $data['effective_from'],
                'effective_until' => $data['effective_until'] ?: null,
                'review_at' => $data['review_at'] ?: null,
                'updated_by' => $user->id,
                'updated_at' => now(),
            ]);

            DB::table('clinic_staff_responsibility_events')->insert([
                'responsibility_id' => $responsibility,
                'actor_user_id' => $user->id,
                'event_type' => 'updated',
                'old_values' => json_encode($old, JSON_UNESCAPED_UNICODE),
                'new_values' => json_encode($data, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'شرح وظایف و مسئولیت به‌روزرسانی شد.');
    }

    public function destroy(int $responsibility)
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        $this->authorizeSecretaryPermission('staff.attendance.view', $doctor);

        $organizationIds = $doctor->clinicOrganizations()
            ->wherePivot('status', 'active')
            ->where('owner_user_id', $user->id)
            ->pluck('clinic_organizations.id');

        $record = DB::table('clinic_staff_responsibilities')
            ->where('id', $responsibility)
            ->whereIn('organization_id', $organizationIds)
            ->whereNull('deleted_at')
            ->first();
        abort_unless($record, 404);

        DB::transaction(function () use ($record, $user): void {
            DB::table('clinic_staff_responsibilities')->where('id', $record->id)->update([
                'deleted_at' => now(),
                'updated_by' => $user->id,
                'updated_at' => now(),
            ]);
            DB::table('clinic_staff_responsibility_events')->insert([
                'responsibility_id' => $record->id,
                'actor_user_id' => $user->id,
                'event_type' => 'deleted',
                'old_values' => json_encode((array) $record, JSON_UNESCAPED_UNICODE),
                'created_at' => now(),
            ]);
        });

        return back()->with('success', 'مسئولیت بایگانی شد.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'organization_id' => ['required', 'integer'],
            'clinic_id' => ['required', 'integer'],
            'staff_user_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'min:3', 'max:160'],
            'description' => ['required', 'string', 'min:5', 'max:5000'],
            'priority' => ['required', Rule::in(['normal', 'important', 'critical'])],
            'status' => ['required', Rule::in(['active', 'paused', 'completed'])],
            'effective_from' => ['required', 'date'],
            'effective_until' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'review_at' => ['nullable', 'date'],
        ]);
    }

    private function assertAssignment(int $organizationId, int $clinicId, int $staffUserId): void
    {
        abort_unless(DB::table('clinic_organization_clinics')
            ->where('organization_id', $organizationId)
            ->where('clinic_id', $clinicId)
            ->where('status', 'active')
            ->exists(), 422, 'شعبه انتخاب‌شده متعلق به مجموعه فعال نیست.');

        $member = DB::table('clinic_organization_members')
            ->where('organization_id', $organizationId)
            ->where('user_id', $staffUserId)
            ->where('is_active', true)
            ->first();

        abort_unless($member, 422, 'کارمند انتخاب‌شده عضو فعال این مجموعه نیست.');
        $clinicIds = is_string($member->clinic_ids) ? (json_decode($member->clinic_ids, true) ?: []) : (array) $member->clinic_ids;
        abort_unless(in_array($clinicId, array_map('intval', $clinicIds), true), 422, 'کارمند به شعبه انتخاب‌شده تخصیص ندارد.');
    }
}
