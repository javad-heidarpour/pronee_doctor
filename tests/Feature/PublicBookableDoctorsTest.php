<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Home\BuildPublicDoctorBookingPageAction;
use App\Actions\Home\ListPublicBookableDoctorsAction;
use App\Models\Doctor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class PublicBookableDoctorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_discovery_lists_only_active_doctors_with_a_real_free_future_slot_at_an_active_clinic(): void
    {
        $bookableDoctor = $this->createDoctor();
        $this->createClinicWithSlot($bookableDoctor, 'available');

        $reservedDoctor = $this->createDoctor();
        $reservedSlot = $this->createClinicWithSlot($reservedDoctor, 'available');
        $this->createAppointment($reservedDoctor, $reservedSlot);

        $bookedDoctor = $this->createDoctor();
        $this->createClinicWithSlot($bookedDoctor, 'booked');

        $inactiveClinicDoctor = $this->createDoctor();
        $this->createClinicWithSlot($inactiveClinicDoctor, 'available', false);

        $inactiveDoctor = $this->createDoctor(['is_active' => false]);
        $this->createClinicWithSlot($inactiveDoctor, 'available');

        $doctors = app(ListPublicBookableDoctorsAction::class)->handle();

        $this->assertCount(1, $doctors);
        $this->assertSame($bookableDoctor->id, $doctors->first()['id']);
        $this->assertSame('09:00', $doctors->first()['next_available']['start_time']);
        $this->assertSame(1, count($doctors->first()['clinics']));
        $this->assertSame('مطب اصلی', $doctors->first()['next_clinic']['name']);
    }

    public function test_related_alternatives_prioritize_same_specialty_and_explain_the_match(): void
    {
        $cityOne = DB::table('cities')->insertGetId([
            'name' => 'شهر اول',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $cityTwo = DB::table('cities')->insertGetId([
            'name' => 'شهر دوم',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $specialtyOne = DB::table('specialties')->insertGetId([
            'name' => 'تخصص اول',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $specialtyTwo = DB::table('specialties')->insertGetId([
            'name' => 'تخصص دوم',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reference = $this->createDoctor([
            'city_id' => $cityOne,
            'specialty_id' => $specialtyOne,
        ]);
        $sameSpecialty = $this->createDoctor([
            'city_id' => $cityTwo,
            'specialty_id' => $specialtyOne,
        ]);
        $sameCity = $this->createDoctor([
            'city_id' => $cityOne,
            'specialty_id' => $specialtyTwo,
        ]);
        $unrelated = $this->createDoctor([
            'city_id' => $cityTwo,
            'specialty_id' => $specialtyTwo,
        ]);

        foreach ([$sameSpecialty, $sameCity, $unrelated] as $doctor) {
            $this->createClinicWithSlot($doctor, 'available');
        }

        $alternatives = app(ListPublicBookableDoctorsAction::class)->handle($reference, 3);

        $this->assertCount(2, $alternatives);
        $this->assertSame($sameSpecialty->id, $alternatives[0]['id']);
        $this->assertSame('تخصص مشابه', $alternatives[0]['match_reason']);
        $this->assertSame($sameCity->id, $alternatives[1]['id']);
        $this->assertSame('در همان شهر', $alternatives[1]['match_reason']);
    }

    public function test_public_booking_page_displays_only_free_slots_from_active_clinics(): void
    {
        $doctor = $this->createDoctor();
        $availableSlot = $this->createClinicWithSlot($doctor, 'available');
        $clinicId = DB::table('time_slots')->where('id', $availableSlot)->value('clinic_id');

        DB::table('time_slots')->insert([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => 'booked',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $page = app(BuildPublicDoctorBookingPageAction::class)->handle($doctor);

        $this->assertCount(1, $page['clinicsWithSlots']);
        $this->assertCount(1, $page['clinicsWithSlots']->first()['dates']);
        $this->assertCount(1, $page['clinicsWithSlots']->first()['dates']->first()['slots']);
        $this->assertSame('available', $page['clinicsWithSlots']->first()['dates']->first()['slots']->first()['status']);
    }

    public function test_latest_available_slot_uses_the_most_recent_create_or_release_timestamp(): void
    {
        $olderDoctor = $this->createDoctor();
        $olderSlot = $this->createClinicWithSlot($olderDoctor, 'available');
        DB::table('time_slots')->where('id', $olderSlot)->update([
            'created_at' => now()->subDays(4),
            'updated_at' => now()->subDays(4),
        ]);

        $recentlyReleasedDoctor = $this->createDoctor();
        $releasedSlot = $this->createClinicWithSlot($recentlyReleasedDoctor, 'available');
        $releasedAt = now()->subMinutes(10)->startOfSecond();
        DB::table('time_slots')->where('id', $releasedSlot)->update([
            'created_at' => now()->subDays(5),
            'updated_at' => $releasedAt,
        ]);

        $doctors = app(ListPublicBookableDoctorsAction::class)->handle();
        $latestFirst = $doctors
            ->sortByDesc(fn (array $doctor): string => $doctor['latest_available']['available_at'])
            ->values();

        $this->assertSame($recentlyReleasedDoctor->id, $latestFirst->first()['id']);
        $this->assertSame(
            $releasedAt->toIso8601String(),
            $latestFirst->first()['latest_available']['available_at']
        );
    }

    private function createDoctor(array $attributes = []): Doctor
    {
        $unique = Str::lower(Str::random(10));

        return Doctor::factory()->create(array_merge([
            'name' => 'پزشک ' . $unique,
            'family' => 'آزمایشی',
            'slug' => 'doctor-' . $unique,
        ], $attributes));
    }

    private function createClinicWithSlot(
        Doctor $doctor,
        string $slotStatus,
        bool $clinicActive = true
    ): int {
        $clinicId = DB::table('addresses')->insertGetId([
            'doctor_id' => $doctor->id,
            'name' => 'مطب اصلی',
            'full_address' => 'نشانی آزمایشی',
            'is_active' => $clinicActive,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('time_slots')->insertGetId([
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00:00',
            'end_time' => '09:30:00',
            'status' => $slotStatus,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createAppointment(Doctor $doctor, int $slotId): void
    {
        $slot = DB::table('time_slots')->where('id', $slotId)->first();

        DB::table('appointments')->insert([
            'doctor_id' => $doctor->id,
            'clinic_id' => $slot->clinic_id,
            'time_slot_id' => $slotId,
            'patient_name' => 'بیمار آزمایشی',
            'patient_phone' => '09120000000',
            'status' => 'pending',
            'appointment_date' => $slot->date,
            'appointment_time' => $slot->start_time,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
