<?php

declare(strict_types=1);

namespace App\Actions\Portal\WorkingHours;

use App\Actions\Portal\Clinic\EnsureDoctorOwnsActiveClinicAction;
use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorHoliday;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreateDoctorHolidayAction
{
    public function __construct(
        private readonly EnsureDoctorOwnsActiveClinicAction $ensureClinic
    ) {
    }

    public function handle(Doctor $doctor, array $data, ?int $createdBy): DoctorHoliday
    {
        return DB::transaction(function () use ($doctor, $data, $createdBy): DoctorHoliday {
            Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();
            $this->ensureClinic->handle($doctor, (int) $data['clinic_id']);

            $date = (string) $data['holiday_date'];

            if ($date < today()->toDateString()) {
                throw ValidationException::withMessages([
                    'holiday_date' => 'ثبت تعطیلی برای تاریخ گذشته امکان‌پذیر نیست.',
                ]);
            }

            $alreadyExists = DoctorHoliday::query()
                ->where('doctor_id', $doctor->id)
                ->where('clinic_id', (int) $data['clinic_id'])
                ->whereDate('holiday_date', $date)
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'holiday_date' => 'برای این مطب در تاریخ انتخاب‌شده قبلاً تعطیلی ثبت شده است.',
                ]);
            }

            $activeAppointmentExists = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('clinic_id', (int) $data['clinic_id'])
                ->whereDate('appointment_date', $date)
                ->whereIn('status', ['pending', 'confirmed', 'contacted'])
                ->exists();

            if ($activeAppointmentExists) {
                throw ValidationException::withMessages([
                    'holiday_date' => 'برای این تاریخ نوبت فعال ثبت شده است؛ ابتدا نوبت‌ها را تعیین‌تکلیف کنید.',
                ]);
            }

            $holiday = DoctorHoliday::query()->create([
                'doctor_id' => $doctor->id,
                'clinic_id' => (int) $data['clinic_id'],
                'holiday_date' => $date,
                'title' => trim((string) $data['title']),
                'note' => isset($data['note']) ? trim((string) $data['note']) : null,
                'created_by' => $createdBy,
            ]);

            TimeSlot::query()
                ->where('doctor_id', $doctor->id)
                ->where('clinic_id', (int) $data['clinic_id'])
                ->whereDate('date', $date)
                ->whereIn('status', ['available', 'booked'])
                ->lockForUpdate()
                ->update([
                    'status' => 'cancelled',
                    'cancelled_reason' => 'holiday',
                ]);

            return $holiday;
        });
    }
}
