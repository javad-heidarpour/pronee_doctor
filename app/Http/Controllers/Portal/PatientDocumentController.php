<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PatientDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Throwable;

final class PatientDocumentController extends Controller
{
    use HasActiveDoctor;

    private const CATEGORIES = [
        'lab_result',
        'imaging',
        'prescription',
        'referral',
        'discharge',
        'consent',
        'other',
    ];

    public function index(int $patient, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeClinicalDocumentView($doctor);

        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403, 'هیچ مطب فعالی برای مشاهده اسناد در دسترس نیست.');

        $patientModel = $this->scopedPatient($patient, $doctor->id, $clinicIds);

        $documents = PatientDocument::query()
            ->where('doctor_id', $doctor->id)
            ->where('patient_id', $patientModel->id)
            ->whereIn('clinic_id', $clinicIds)
            ->whereNull('archived_at')
            ->with(['clinic:id,name', 'uploadedBy:id,name,family'])
            ->orderByDesc('uploaded_at')
            ->paginate(20)
            ->withQueryString();

        $audit->handle('patient.documents.viewed', null, [
            'doctor_id' => $doctor->id,
            'patient_id' => $patientModel->id,
        ]);

        $user = Auth::guard('web')->user();

        return Inertia::render('Portal/Patients/Documents', [
            'doctor' => $doctor,
            'patient' => [
                'id' => $patientModel->id,
                'name' => $patientModel->name,
                'phone' => $patientModel->phone,
            ],
            'documents' => $documents,
            'clinics' => Address::query()->whereIn('id', $clinicIds)->orderBy('name')->get(['id', 'name']),
            'canManage' => $user?->role !== 'secretary'
                || $this->secretaryHasPermission($doctor, 'patients.clinical.manage'),
            'categories' => [
                ['value' => 'lab_result', 'label' => 'نتیجه آزمایش'],
                ['value' => 'imaging', 'label' => 'تصویربرداری'],
                ['value' => 'prescription', 'label' => 'نسخه'],
                ['value' => 'referral', 'label' => 'ارجاع'],
                ['value' => 'discharge', 'label' => 'خلاصه ترخیص'],
                ['value' => 'consent', 'label' => 'رضایت‌نامه'],
                ['value' => 'other', 'label' => 'سایر اسناد'],
            ],
            'maxUploadKb' => 20480,
        ]);
    }

    public function store(
        Request $request,
        int $patient,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);

        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403, 'هیچ مطب فعالی برای ثبت سند در دسترس نیست.');

        $data = $request->validate([
            'clinic_id' => [
                'required',
                'integer',
                Rule::exists('addresses', 'id')
                    ->where('doctor_id', $doctor->id)
                    ->where('is_active', true),
            ],
            'appointment_id' => [
                'nullable',
                'integer',
                Rule::exists('appointments', 'id')
                    ->where('doctor_id', $doctor->id)
                    ->where('patient_id', $patient),
            ],
            'category' => ['required', Rule::in(self::CATEGORIES)],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:3000'],
            'file' => [
                'required',
                'file',
                'mimetypes:application/pdf,image/jpeg,image/png,image/webp,image/tiff,application/dicom',
                'max:20480',
            ],
        ]);

        $clinicId = (int) $data['clinic_id'];
        abort_unless(in_array($clinicId, $clinicIds, true), 403, 'به مطب انتخاب‌شده دسترسی ندارید.');

        $patientModel = $this->scopedPatient($patient, $doctor->id, [$clinicId]);

        if (!empty($data['appointment_id'])) {
            Appointment::query()
                ->whereKey($data['appointment_id'])
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patientModel->id)
                ->where('clinic_id', $clinicId)
                ->firstOrFail();
        }

        $file = $data['file'];
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension() ?: 'bin');
        $filename = Str::uuid()->toString() . '.' . $extension;
        $directory = 'patient-documents/' . $doctor->id . '/' . $patientModel->id;
        $storedPath = Storage::disk('local')->putFileAs($directory, $file, $filename);

        abort_unless(is_string($storedPath) && $storedPath !== '', 500, 'ذخیره امن فایل انجام نشد.');

        $originalName = $this->safeOriginalName($file->getClientOriginalName());
        $mimeType = (string) ($file->getMimeType() ?: 'application/octet-stream');
        $checksum = hash_file('sha256', $file->getRealPath());

        try {
            $document = DB::transaction(fn (): PatientDocument => PatientDocument::query()->create([
                'patient_id' => $patientModel->id,
                'doctor_id' => $doctor->id,
                'clinic_id' => $clinicId,
                'appointment_id' => $data['appointment_id'] ?? null,
                'uploaded_by_user_id' => Auth::guard('web')->id(),
                'category' => $data['category'],
                'title' => trim($data['title']),
                'description' => $data['description'] ?? null,
                'original_name' => $originalName,
                'disk' => 'local',
                'path' => $storedPath,
                'mime_type' => $mimeType,
                'file_size' => (int) $file->getSize(),
                'checksum_sha256' => $checksum,
                'uploaded_at' => now(),
            ]), 3);
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($storedPath);
            throw $exception;
        }

        $audit->handle('patient.document.uploaded', $document, [
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'category' => $document->category,
            'file_size' => $document->file_size,
        ]);

        return back()->with('success', 'سند پزشکی به‌صورت خصوصی ذخیره شد.');
    }

    public function download(int $patient, int $document, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeClinicalDocumentView($doctor);

        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403);

        $model = $this->scopedDocument($patient, $document, $doctor->id, $clinicIds);
        abort_unless($model->disk === 'local', 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($model->path), 404, 'فایل سند در فضای خصوصی موجود نیست.');

        $audit->handle('patient.document.downloaded', $model, [
            'doctor_id' => $doctor->id,
            'clinic_id' => $model->clinic_id,
            'category' => $model->category,
        ]);

        return $disk->download($model->path, $model->original_name, [
            'Content-Type' => $model->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function archive(int $patient, int $document, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);

        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403);

        $model = DB::transaction(function () use ($patient, $document, $doctor, $clinicIds): PatientDocument {
            $model = $this->scopedDocument($patient, $document, $doctor->id, $clinicIds, lock: true);
            $model->update([
                'archived_at' => now(),
                'archived_by_user_id' => Auth::guard('web')->id(),
            ]);

            return $model->refresh();
        }, 3);

        $audit->handle('patient.document.archived', $model, [
            'doctor_id' => $doctor->id,
            'clinic_id' => $model->clinic_id,
            'category' => $model->category,
        ]);

        return back()->with('success', 'سند بایگانی شد؛ فایل اصلی حذف نشده است.');
    }

    private function scopedPatient(int $patientId, int $doctorId, array $clinicIds): Patient
    {
        return Patient::query()
            ->whereKey($patientId)
            ->where('doctor_id', $doctorId)
            ->whereHas('appointments', fn ($query) => $query->whereIn('clinic_id', $clinicIds))
            ->firstOrFail();
    }

    private function scopedDocument(
        int $patientId,
        int $documentId,
        int $doctorId,
        array $clinicIds,
        bool $lock = false
    ): PatientDocument {
        $query = PatientDocument::query()
            ->whereKey($documentId)
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->whereIn('clinic_id', $clinicIds)
            ->whereNull('archived_at');

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    private function authorizeClinicalDocumentView(\App\Models\Doctor $doctor): void
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);

        if ($user->role !== 'secretary') {
            return;
        }

        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        abort_unless(
            is_array($permissions)
                && (in_array('patients.clinical.view', $permissions, true)
                    || in_array('patients.clinical.manage', $permissions, true)),
            403,
            'مجوز مشاهده اسناد بالینی برای حساب شما فعال نیست.'
        );
    }

    private function secretaryHasPermission(\App\Models\Doctor $doctor, string $permission): bool
    {
        $user = Auth::guard('web')->user();
        if (!$user || $user->role !== 'secretary') {
            return false;
        }

        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return is_array($permissions) && in_array($permission, $permissions, true);
    }

    private function safeOriginalName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^\pL\pN._ -]+/u', '_', $name) ?: 'medical-document';
        $name = trim($name, " .\t\n\r\0\x0B");

        return mb_substr($name !== '' ? $name : 'medical-document', 0, 255);
    }
}
