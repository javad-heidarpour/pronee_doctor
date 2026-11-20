<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Models\PatientAccountAccess;
use App\Models\PatientConsent;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

final class PatientConsentSigningController extends Controller
{
    public function show(int $consent, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();
        $model = PatientConsent::query()
            ->whereKey($consent)
            ->whereHas('patient.accountAccesses', fn ($query) => $query->active()
                ->where('user_id', $user->id)
                ->where('access_type', 'owner'))
            ->with(['patient:id,name', 'doctor:id,name,family,specialty_title', 'clinic:id,name'])
            ->firstOrFail();

        abort_unless($model->hasValidContentHash(), 409, 'یکپارچگی متن رضایت‌نامه تأیید نشد؛ امضا متوقف شد.');
        if ($model->status === PatientConsent::STATUS_SIGNED) {
            abort_unless($model->hasValidSignature(), 409, 'یکپارچگی امضای الکترونیکی تأیید نشد.');
        }

        $audit->handle('patient.consent.viewed', $model);

        return Inertia::render('Patient/Consent', [
            'consent' => $model,
            'patientName' => $model->patient->name,
        ]);
    }

    public function sign(Request $request, int $consent, RecordSecurityAuditEventAction $audit)
    {
        $user = $this->authenticatedPatient();
        $data = $request->validate([
            'signer_name' => ['required', 'string', 'max:180'],
            'accept' => ['required', 'accepted'],
        ]);

        $signed = DB::transaction(function () use ($user, $consent, $data, $request): PatientConsent {
            $model = PatientConsent::query()
                ->whereKey($consent)
                ->lockForUpdate()
                ->firstOrFail();

            PatientAccountAccess::query()->active()
                ->where('user_id', $user->id)
                ->where('access_type', 'owner')
                ->where('patient_id', $model->patient_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($model->status === PatientConsent::STATUS_PENDING, 409, 'این رضایت‌نامه قبلاً امضا یا تعیین تکلیف شده است.');
            abort_unless($model->hasValidContentHash(), 409, 'محتوای رضایت‌نامه پس از ثبت تغییر کرده است؛ امضا متوقف شد.');

            $patientName = (string) $model->patient()->value('name');
            $signerName = trim(preg_replace('/\s+/u', ' ', $data['signer_name']) ?? $data['signer_name']);
            abort_unless($this->normalizeName($signerName) === $this->normalizeName($patientName), 422, 'نام امضاکننده باید با نام ثبت‌شده بیمار مطابقت داشته باشد.');

            $signedAt = now();
            $signaturePayload = implode('|', [
                $model->id,
                $model->patient_id,
                $user->id,
                $model->content_sha256,
                $signerName,
                $signedAt->toIso8601String(),
            ]);
            $signatureHash = hash_hmac('sha256', $signaturePayload, (string) config('app.key'));

            $model->update([
                'status' => PatientConsent::STATUS_SIGNED,
                'signed_by_user_id' => $user->id,
                'signer_name' => $signerName,
                'signature_method' => 'authenticated_typed_name',
                'signature_sha256' => $signatureHash,
                'signed_at' => $signedAt,
                'signer_ip_address' => $request->ip(),
                'signer_user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ]);

            return $model->refresh();
        }, 3);

        $audit->handle('patient.consent.signed', $signed, [
            'changed_fields' => ['status', 'signature'],
        ]);

        return redirect()->route('patient.health.consents.show', $signed->id)
            ->with('success', 'امضای الکترونیکی شما ثبت و رضایت‌نامه قفل شد.');
    }

    private function authenticatedPatient(): User
    {
        $user = Auth::guard('web')->user();
        abort_unless($user instanceof User && $user->role === 'user' && $user->is_active, 403);

        return $user;
    }

    private function normalizeName(string $value): string
    {
        $value = str_replace(['ي', 'ك'], ['ی', 'ک'], trim($value));

        return mb_strtolower(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
