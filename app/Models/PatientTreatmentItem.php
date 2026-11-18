<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientTreatmentItem extends Model
{
    protected static function booted(): void
    {
        static::updating(function (self $item): void {
            if ($item->getOriginal('stage') === 'completed') {
                $allowedFinancialFields = ['recognized_amount', 'paid_applied_amount', 'updated_at'];
                if (array_diff(array_keys($item->getDirty()), $allowedFinancialFields) !== []) {
                    throw ValidationException::withMessages(['treatment' => 'اطلاعات بالینی مرحله تکمیل‌شده قابل ویرایش نیست.']);
                }
            } elseif ($item->getOriginal('stage') === 'cancelled') {
                throw ValidationException::withMessages(['treatment' => 'مرحله لغوشده قابل ویرایش نیست.']);
            }
        });
        static::deleting(function (self $item): void {
            if (in_array($item->stage, ['completed', 'cancelled'], true)) {
                throw ValidationException::withMessages(['treatment' => 'مرحله نهایی درمان قابل حذف نیست.']);
            }
        });
    }

    protected $fillable = ['treatment_plan_id','service_id','provider_doctor_id','appointment_id','item_type','title','tooth_number','tooth_surface','sequence','stage','price','recognized_amount','paid_applied_amount','doctor_share_basis_points','follow_up_after_days','performed_at','follow_up_due_at','clinical_notes','completed_by'];
    protected $casts = ['price' => 'integer','recognized_amount' => 'integer','paid_applied_amount' => 'integer','performed_at' => 'datetime','follow_up_due_at' => 'datetime'];
    public function plan(): BelongsTo { return $this->belongsTo(PatientTreatmentPlan::class, 'treatment_plan_id'); }
    public function service(): BelongsTo { return $this->belongsTo(ClinicService::class, 'service_id'); }
    public function provider(): BelongsTo { return $this->belongsTo(Doctor::class, 'provider_doctor_id'); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function completer(): BelongsTo { return $this->belongsTo(User::class, 'completed_by'); }
}