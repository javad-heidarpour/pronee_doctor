<?php

namespace App\Actions\Portal\Users;

use App\Models\Doctor;

class BuildSecretaryAssignmentsDataAction
{
    /**
     * Validate doctor and clinic ownership, then build pivot data.
     *
     * @return array<int, array{permissions: string, clinic_ids: string}>
     */
    public function handle(
        array $validated,
        string $actorRole,
        ?Doctor $activeDoctor
    ): array {
        $doctorIds = collect($validated['doctors'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($actorRole === 'doctor') {
            abort_unless(
                $activeDoctor && count(array_diff($doctorIds, [$activeDoctor->id])) === 0,
                403,
                'شما فقط مجاز به اتصال منشی به پزشک فعال خودتان هستید.'
            );
        }

        $doctors = Doctor::query()
            ->with('user')
            ->whereIn('id', $doctorIds)
            ->get()
            ->keyBy('id');

        foreach ($doctorIds as $doctorId) {
            $doctor = $doctors->get($doctorId);

            abort_unless(
                $doctor &&
                $doctor->user &&
                $doctor->user->role === 'doctor' &&
                $doctor->user->is_active &&
                $doctor->is_active,
                422,
                'یکی از پزشکان انتخاب‌شده معتبر یا فعال نیست.'
            );
        }

        $syncData = [];

        foreach ($validated['doctors'] as $item) {
            $doctor = $doctors->get((int) $item['id']);

            $validClinicIds = $doctor->address()
                ->where('addresses.is_active', true)
                ->whereIn('addresses.id', $item['clinic_ids'])
                ->pluck('addresses.id')
                ->map(fn ($id) => (int) $id)
                ->all();

            abort_unless(
                count($validClinicIds) === count($item['clinic_ids']),
                422,
                'یکی از مطب‌های انتخاب‌شده متعلق به پزشک مربوطه نیست.'
            );

            abort_if(
                count($validClinicIds) === 0,
                422,
                'برای هر پزشک متصل، حداقل یک مطب انتخاب کنید.'
            );

            $syncData[$item['id']] = [
                'permissions' => json_encode(
                    $item['permissions'],
                    JSON_UNESCAPED_UNICODE
                ),
                'clinic_ids' => json_encode(
                    array_values(array_map('intval', $item['clinic_ids'])),
                    JSON_UNESCAPED_UNICODE
                ),
            ];
        }

        return $syncData;
    }
}
