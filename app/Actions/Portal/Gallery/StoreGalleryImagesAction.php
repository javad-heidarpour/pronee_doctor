<?php

declare(strict_types=1);

namespace App\Actions\Portal\Gallery;

use App\Models\Doctor;
use App\Models\DoctorImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class StoreGalleryImagesAction
{
    /**
     * @param  UploadedFile[]  $files
     */
    public function handle(
        Doctor $doctor,
        array $files,
        string $category,
        ?string $caption
    ): void {
        $storedPaths = [];

        try {
            DB::transaction(function () use ($doctor, $files, $category, $caption, &$storedPaths): void {
                // Serialize order allocation for concurrent uploads by the same doctor.
                Doctor::query()->whereKey($doctor->id)->lockForUpdate()->firstOrFail();

                $lastOrder = (int) ($doctor->images()->max('sort_order') ?? 0);

                foreach ($files as $file) {
                    $path = $file->store('doctor-gallery', 'public');

                    if (!is_string($path) || $path === '') {
                        throw new RuntimeException('ذخیره تصویر در فضای عمومی ناموفق بود.');
                    }

                    $storedPaths[] = $path;

                    DoctorImage::query()->create([
                        'doctor_id' => $doctor->id,
                        'image_url' => $path,
                        'caption' => $caption,
                        'category' => $category,
                        'sort_order' => ++$lastOrder,
                        'is_active' => true,
                    ]);
                }
            });
        } catch (Throwable $exception) {
            if ($storedPaths !== []) {
                Storage::disk('public')->delete($storedPaths);
            }

            throw $exception;
        }
    }
}
