<?php

declare(strict_types=1);

namespace App\Actions\Portal\Profile;

use App\Models\Doctor;
use Illuminate\Support\Facades\DB;

final class UpdateDoctorHonorBadgesAction
{
    public function handle(Doctor $doctor, array $badges): void
    {
        DB::transaction(function () use ($doctor, $badges): void {
            $doctor->honorBadges()->delete();

            foreach (array_values($badges) as $index => $badge) {
                $title = trim((string) ($badge['title'] ?? ''));

                if ($title === '') {
                    continue;
                }

                $doctor->honorBadges()->create([
                    'title' => $title,
                    'icon' => trim((string) ($badge['icon'] ?? '')) ?: '🏅',
                    'sort_order' => $index + 1,
                ]);
            }
        });
    }
}
