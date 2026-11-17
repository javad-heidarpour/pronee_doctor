<?php

namespace App\Actions\Security;

use App\Models\SecurityAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RecordSecurityAuditEventAction
{
    /**
     * ثبت رویداد بدون ذخیره داده‌های حساس فرم، رمز عبور یا توکن‌ها.
     */
    public function handle(
        string $event,
        ?Model $subject = null,
        array $metadata = []
    ): void {
        $safeMetadata = array_intersect_key($metadata, array_flip([
            'role',
            'is_active',
            'doctor_id',
            'clinic_count',
            'permission_count',
            'restored',
            'sessions_revoked',
            'role_changed',
            'status_changed',
            'password_changed',
            'reconciliation_status',
            'changed_fields',
        ]));

        SecurityAuditLog::create([
            'actor_id' => Auth::guard('web')->id(),
            'event' => $event,
            'subject_type' => $subject ? $subject::class : null,
            'subject_id' => $subject?->getKey(),
            'metadata' => $safeMetadata ?: null,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
        ]);
    }
}
