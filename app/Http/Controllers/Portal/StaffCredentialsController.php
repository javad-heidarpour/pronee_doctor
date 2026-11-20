<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicOrganization;
use App\Models\ClinicStaffCredential;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

final class StaffCredentialsController extends Controller
{
    use HasActiveDoctor;

    private const TYPES = [
        'medical_license',
        'specialty_certificate',
        'training_certificate',
        'professional_card',
        'other',
    ];

    public function index(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $doctor = $this->currentDoctor();
        $organizationId = $request->integer('organization_id');
        if ($organizationId < 1) {
            $organizationId = (int) $doctor->clinicOrganizations()
                ->wherePivot('status', 'active')
                ->where('clinic_organizations.status', 'active')
                ->orderBy('clinic_organizations.id')
                ->value('clinic_organizations.id');
        }
        $organization = $this->organization($doctor, $organizationId);
        $access = $this->accessFor($organization, $user, $doctor);

        $query = ClinicStaffCredential::query()
            ->where('organization_id', $organization->id)
            ->whereNull('archived_at');

        if (!$access['is_owner']) {
            $query->where('staff_user_id', $user->id)
                ->whereIn('clinic_id', $access['clinic_ids']);
        }

        $credentials = $query
            ->with([
                'staff:id,name,family',
                'clinic:id,name',
                'uploadedBy:id,name,family',
                'verifiedBy:id,name,family',
            ])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $credentials->getCollection()->transform(function (ClinicStaffCredential $credential): ClinicStaffCredential {
            $credential->setAttribute('is_expired', $credential->isExpired());
            return $credential;
        });

        $staffOptions = $access['is_owner']
            ? $this->organizationStaffOptions($organization)
            : [[
                'id' => $user->id,
                'label' => trim(($user->name ?? '') . ' ' . ($user->family ?? '')),
            ]];

        $clinics = Address::query()
            ->whereIn('id', $access['clinic_ids'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('Portal/StaffCredentials/Index', [
            'doctor' => $doctor,
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'credentials' => $credentials,
            'staffOptions' => $staffOptions,
            'clinics' => $clinics,
            'credentialTypes' => [
                ['value' => 'medical_license', 'label' => 'پروانه فعالیت'],
                ['value' => 'specialty_certificate', 'label' => 'گواهی تخصص'],
                ['value' => 'training_certificate', 'label' => 'گواهی دوره آموزشی'],
                ['value' => 'professional_card', 'label' => 'کارت حرفه‌ای'],
                ['value' => 'other', 'label' => 'سایر مدارک'],
            ],
            'canManage' => $access['is_owner'],
            'maxUploadKb' => 10240,
        ]);
    }

    public function store(
        Request $request,
        RecordSecurityAuditEventAction $audit
    ) {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $doctor = $this->currentDoctor();
        $organization = $this->organization($doctor, $request->integer('organization_id'));
        $access = $this->accessFor($organization, $user, $doctor);

        $data = $request->validate([
            'organization_id' => ['required', 'integer'],
            'clinic_id' => ['required', 'integer'],
            'staff_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'credential_type' => ['required', Rule::in(self::TYPES)],
            'title' => ['required', 'string', 'max:180'],
            'issuer' => ['nullable', 'string', 'max:180'],
            'license_number' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:issued_at'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,webp', 'max:10240'],
        ]);

        $targetUserId = $access['is_owner']
            ? (int) ($data['staff_user_id'] ?? 0)
            : (int) $user->id;

        abort_unless($targetUserId > 0, 422, 'انتخاب کارمند الزامی است.');
        abort_unless(
            in_array((int) $data['clinic_id'], $access['clinic_ids'], true),
            403,
            'به شعبه انتخاب‌شده دسترسی ندارید.'
        );
        $this->assertOrganizationStaff($organization, $targetUserId, (int) $data['clinic_id']);

        $file = $data['file'];
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $filename = Str::uuid()->toString() . '.' . $extension;
        $directory = 'staff-credentials/' . $organization->id . '/' . $targetUserId;
        $storedPath = Storage::disk('local')->putFileAs($directory, $file, $filename);

        abort_unless(is_string($storedPath) && $storedPath !== '', 500, 'ذخیره امن فایل انجام نشد.');

        $originalName = $this->safeOriginalName($file->getClientOriginalName());
        $mimeType = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $checksum = hash_file('sha256', $file->getRealPath());

        try {
            $credential = DB::transaction(fn (): ClinicStaffCredential => ClinicStaffCredential::query()->create([
                'organization_id' => $organization->id,
                'clinic_id' => (int) $data['clinic_id'],
                'staff_user_id' => $targetUserId,
                'uploaded_by_user_id' => $user->id,
                'credential_type' => $data['credential_type'],
                'title' => trim($data['title']),
                'issuer' => isset($data['issuer']) ? trim($data['issuer']) : null,
                'license_number' => isset($data['license_number']) ? trim($data['license_number']) : null,
                'issued_at' => $data['issued_at'] ?? null,
                'expires_at' => $data['expires_at'] ?? null,
                'original_name' => $originalName,
                'disk' => 'local',
                'path' => $storedPath,
                'mime_type' => $mimeType,
                'file_size' => (int) $file->getSize(),
                'checksum_sha256' => $checksum,
                'status' => 'pending',
            ]), 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        $audit->handle('staff.credential.uploaded', $credential, [
            'organization_id' => $organization->id,
            'clinic_id' => $credential->clinic_id,
            'staff_user_id' => $targetUserId,
            'credential_type' => $credential->credential_type,
            'file_size' => $credential->file_size,
        ]);

        return back()->with('success', 'مدرک به‌صورت خصوصی ثبت شد و برای تأیید مدیر سازمان در صف قرار گرفت.');
    }

    public function download(
        int $credential,
        RecordSecurityAuditEventAction $audit
    ) {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $doctor = $this->currentDoctor();
        $model = ClinicStaffCredential::query()->whereKey($credential)->whereNull('archived_at')->firstOrFail();
        $organization = $this->organization($doctor, (int) $model->organization_id);
        $access = $this->accessFor($organization, $user, $doctor);

        abort_unless(
            $access['is_owner']
                || ((int) $model->staff_user_id === (int) $user->id
                    && in_array((int) $model->clinic_id, $access['clinic_ids'], true)),
            403,
            'اجازه دریافت این مدرک را ندارید.'
        );
        abort_unless($model->disk === 'local' && str_starts_with($model->path, 'staff-credentials/'), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($model->path), 404, 'فایل مدرک در فضای خصوصی موجود نیست.');

        $audit->handle('staff.credential.downloaded', $model, [
            'organization_id' => $organization->id,
            'clinic_id' => $model->clinic_id,
            'staff_user_id' => $model->staff_user_id,
        ]);

        return $disk->download($model->path, $model->original_name, [
            'Content-Type' => $model->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function review(
        Request $request,
        int $credential,
        RecordSecurityAuditEventAction $audit
    ) {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $doctor = $this->currentDoctor();
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verified', 'rejected'])],
            'rejection_reason' => ['required_if:decision,rejected', 'nullable', 'string', 'min:5', 'max:1000'],
        ]);

        $model = DB::transaction(function () use ($credential, $user, $doctor, $data): ClinicStaffCredential {
            $row = ClinicStaffCredential::query()->whereKey($credential)->lockForUpdate()->firstOrFail();
            $organization = $this->organization($doctor, (int) $row->organization_id);
            abort_unless((int) $organization->owner_user_id === (int) $user->id, 403);
            abort_unless($row->status === 'pending', 409, 'فقط مدارک در انتظار بررسی قابل تصمیم‌گیری هستند.');

            $row->update([
                'status' => $data['decision'],
                'verified_by_user_id' => $user->id,
                'verified_at' => now(),
                'rejection_reason' => $data['decision'] === 'rejected' ? trim((string) $data['rejection_reason']) : null,
            ]);

            return $row->refresh();
        }, 3);

        $audit->handle('staff.credential.reviewed', $model, [
            'organization_id' => $model->organization_id,
            'clinic_id' => $model->clinic_id,
            'staff_user_id' => $model->staff_user_id,
            'decision' => $model->status,
        ]);

        return back()->with('success', $model->status === 'verified' ? 'مدرک تأیید شد.' : 'مدرک رد شد.');
    }

    public function archive(
        int $credential,
        RecordSecurityAuditEventAction $audit
    ) {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $doctor = $this->currentDoctor();

        $model = DB::transaction(function () use ($credential, $user, $doctor): ClinicStaffCredential {
            $row = ClinicStaffCredential::query()->whereKey($credential)->whereNull('archived_at')->lockForUpdate()->firstOrFail();
            $organization = $this->organization($doctor, (int) $row->organization_id);
            $access = $this->accessFor($organization, $user, $doctor);

            abort_unless(
                $access['is_owner']
                    || ((int) $row->staff_user_id === (int) $user->id
                        && in_array((int) $row->clinic_id, $access['clinic_ids'], true)),
                403,
                'اجازه بایگانی این مدرک را ندارید.'
            );

            $row->update([
                'archived_at' => now(),
                'archived_by_user_id' => $user->id,
            ]);

            return $row->refresh();
        }, 3);

        $audit->handle('staff.credential.archived', $model, [
            'organization_id' => $model->organization_id,
            'clinic_id' => $model->clinic_id,
            'staff_user_id' => $model->staff_user_id,
        ]);

        return back()->with('success', 'مدرک بایگانی شد؛ فایل اصلی حذف نشده است.');
    }

    private function organization(Doctor $doctor, int $organizationId): ClinicOrganization
    {
        abort_if($organizationId < 1, 422, 'سازمان درمانی انتخاب نشده است.');

        return $doctor->clinicOrganizations()
            ->wherePivot('status', 'active')
            ->where('clinic_organizations.status', 'active')
            ->whereKey($organizationId)
            ->firstOrFail();
    }

    private function accessFor(ClinicOrganization $organization, User $user, Doctor $doctor): array
    {
        $isOwner = (int) $organization->owner_user_id === (int) $user->id;
        $activeClinicIds = DB::table('clinic_organization_clinics')
            ->join('addresses', 'addresses.id', '=', 'clinic_organization_clinics.clinic_id')
            ->where('clinic_organization_clinics.organization_id', $organization->id)
            ->where('clinic_organization_clinics.status', 'active')
            ->where('addresses.is_active', true)
            ->pluck('clinic_organization_clinics.clinic_id')
            ->map(fn ($id) => (int) $id)->all();

        if ($isOwner) {
            return ['is_owner' => true, 'clinic_ids' => $activeClinicIds];
        }

        $member = DB::table('clinic_organization_members')
            ->where('organization_id', $organization->id)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->first();

        if ($member) {
            $assigned = is_array($member->clinic_ids)
                ? $member->clinic_ids
                : (json_decode((string) $member->clinic_ids, true) ?: []);

            $clinicIds = array_values(array_intersect(
                $activeClinicIds,
                array_map('intval', is_array($assigned) ? $assigned : [])
            ));
            abort_unless($clinicIds !== [], 403, 'برای این سازمان شعبه‌ای به حساب شما تخصیص داده نشده است.');

            return ['is_owner' => false, 'clinic_ids' => $clinicIds];
        }

        $doctorMember = DB::table('clinic_organization_doctors')
            ->join('doctors', 'doctors.id', '=', 'clinic_organization_doctors.doctor_id')
            ->where('clinic_organization_doctors.organization_id', $organization->id)
            ->where('clinic_organization_doctors.status', 'active')
            ->where('doctors.user_id', $user->id)
            ->select('doctors.id')
            ->first();

        abort_unless($doctorMember, 403, 'عضویت فعال در این سازمان ندارید.');

        $clinicIds = DB::table('addresses')
            ->whereIn('id', $activeClinicIds)
            ->where('doctor_id', $doctorMember->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)->all();

        abort_unless($clinicIds !== [], 403, 'برای این حساب پزشک شعبه‌ای در سازمان فعال نیست.');

        return ['is_owner' => false, 'clinic_ids' => $clinicIds];
    }

    private function assertOrganizationStaff(ClinicOrganization $organization, int $staffUserId, int $clinicId): void
    {
        $member = DB::table('clinic_organization_members')
            ->where('organization_id', $organization->id)
            ->where('user_id', $staffUserId)
            ->where('is_active', true)
            ->first();

        if ($member) {
            $assigned = is_array($member->clinic_ids)
                ? $member->clinic_ids
                : (json_decode((string) $member->clinic_ids, true) ?: []);

            abort_unless(
                in_array($clinicId, array_map('intval', is_array($assigned) ? $assigned : []), true),
                422,
                'کارمند به شعبه انتخاب‌شده تخصیص داده نشده است.'
            );

            return;
        }

        $doctorMember = DB::table('clinic_organization_doctors')
            ->join('doctors', 'doctors.id', '=', 'clinic_organization_doctors.doctor_id')
            ->where('clinic_organization_doctors.organization_id', $organization->id)
            ->where('clinic_organization_doctors.status', 'active')
            ->where('doctors.user_id', $staffUserId)
            ->select('doctors.id')
            ->first();

        abort_unless($doctorMember, 422, 'فرد انتخاب‌شده عضو فعال سازمان نیست.');

        abort_unless(
            DB::table('clinic_organization_clinics')
                ->join('addresses', 'addresses.id', '=', 'clinic_organization_clinics.clinic_id')
                ->where('clinic_organization_clinics.organization_id', $organization->id)
                ->where('clinic_organization_clinics.clinic_id', $clinicId)
                ->where('clinic_organization_clinics.status', 'active')
                ->where('addresses.doctor_id', $doctorMember->id)
                ->where('addresses.is_active', true)
                ->exists(),
            422,
            'پزشک انتخاب‌شده به شعبه انتخاب‌شده تخصیص ندارد.'
        );
    }

    private function organizationStaffOptions(ClinicOrganization $organization): array
    {
        $members = DB::table('clinic_organization_members')
            ->join('users', 'users.id', '=', 'clinic_organization_members.user_id')
            ->where('clinic_organization_members.organization_id', $organization->id)
            ->where('clinic_organization_members.is_active', true)
            ->select('users.id', 'users.name', 'users.family')
            ->get();

        $doctors = DB::table('clinic_organization_doctors')
            ->join('doctors', 'doctors.id', '=', 'clinic_organization_doctors.doctor_id')
            ->join('users', 'users.id', '=', 'doctors.user_id')
            ->where('clinic_organization_doctors.organization_id', $organization->id)
            ->where('clinic_organization_doctors.status', 'active')
            ->select('users.id', 'users.name', 'users.family')
            ->get();

        return $members->concat($doctors)->unique('id')->map(fn ($row) => [
            'id' => (int) $row->id,
            'label' => trim(($row->name ?? '') . ' ' . ($row->family ?? '')),
        ])->values()->all();
    }

    private function safeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\pL\pN._ -]+/u', '_', $name) ?: 'credential';
        $name = trim($name, " .\t\n\r\0\x0B");

        return mb_substr($name !== '' ? $name : 'credential', 0, 255);
    }
}
