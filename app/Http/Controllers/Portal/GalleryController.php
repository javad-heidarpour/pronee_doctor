<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Gallery\DeleteGalleryImageAction;
use App\Actions\Portal\Gallery\ListGalleryImagesAction;
use App\Actions\Portal\Gallery\StoreGalleryImagesAction;
use App\Actions\Portal\Gallery\UpdateGalleryImageAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\StoreGalleryImagesRequest;
use App\Http\Requests\Portal\UpdateGalleryImageRequest;
use App\Models\Doctor;
use App\Models\DoctorImage;
use Inertia\Inertia;

class GalleryController extends Controller
{
    use HasActiveDoctor;

    public function index(ListGalleryImagesAction $action)
    {
        $doctor = $this->currentDoctor();

        return Inertia::render('Portal/Gallery', $action->handle($doctor));
    }

    public function store(
        StoreGalleryImagesRequest $request,
        StoreGalleryImagesAction $action
    ) {
        $doctor = $this->currentDoctor();
        $validated = $request->validated();

        $action->handle(
            $doctor,
            $request->file('images'),
            $validated['category'],
            $validated['caption'] ?? null,
        );

        return back()->with('success', 'تصاویر با موفقیت اضافه شدند.');
    }

    public function update(
        UpdateGalleryImageRequest $request,
        DoctorImage $image,
        UpdateGalleryImageAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeImage($image, $doctor);

        $action->handle($image, $request->validated());

        return back()->with('success', 'تصویر بروزرسانی شد.');
    }

    public function destroy(
        DoctorImage $image,
        DeleteGalleryImageAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeImage($image, $doctor);

        $action->handle($image);

        return back()->with('success', 'تصویر حذف شد.');
    }

    private function authorizeImage(DoctorImage $image, Doctor $doctor): void
    {
        abort_unless(
            (int) $image->doctor_id === (int) $doctor->id,
            403,
            'شما به این تصویر دسترسی ندارید.'
        );
    }
}