<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TimeSlot extends Model
{
    protected $fillable = [
        'doctor_id', 'clinic_id', 'date', 'start_time', 'end_time', 'status'
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'clinic_id');
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function scopePubliclyBookable(Builder $query): Builder
    {
        $minimumAt = now()->addHour();
        $minimumDate = $minimumAt->toDateString();
        $minimumTime = $minimumAt->format('H:i:s');

        return $query
            ->where('status', 'available')
            ->where(function (Builder $builder) use ($minimumDate, $minimumTime): void {
                $builder
                    ->where('date', '>', $minimumDate)
                    ->orWhere(function (Builder $sameDay) use ($minimumDate, $minimumTime): void {
                        $sameDay
                            ->where('date', $minimumDate)
                            ->where('start_time', '>=', $minimumTime);
                    });
            })
            ->whereDoesntHave('appointment', fn (Builder $appointment): Builder => $appointment
                ->whereIn('status', ['pending', 'confirmed', 'contacted']));
    }

    public function isBooked(): bool
    {
        return $this->status === 'booked' || $this->appointment()
            ->whereIn('status', ['pending', 'confirmed', 'contacted'])
            ->exists();
    }

    public function scopeOverlapping(
        Builder $query,
        int $doctorId,
        string $date,
        string $startTime,
        string $endTime,
        ?int $ignoreId = null
    ): Builder {
        return $query
            ->where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->where('status', '!=', 'cancelled')
            ->where('start_time', '<', $endTime)
            ->where('end_time', '>', $startTime)
            ->when($ignoreId !== null, function (Builder $builder) use ($ignoreId) {
                $builder->where('id', '!=', $ignoreId);
            });
    }
}
