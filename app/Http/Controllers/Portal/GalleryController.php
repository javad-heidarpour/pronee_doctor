<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Gallery\DeleteGalleryImageAction;
use App\Actions\Portal\Gallery\ListGalleryImagesAction;
use App\Actions\Portal\Gallery\StoreGalleryImagesAction;
use App\Actions\Portal\Gallery\UpdateGalleryImageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\StoreGalleryImagesRequest;
use App\Http\Requests\Portal\UpdateGalleryImageRequest;
use App\Models\DoctorImage;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GalleryController extends Controller
{
    public function index(ListGalleryImagesAction $action)
    {
        return Inertia::render('Portal/Gallery', $action->handle());
    }

    public function store(StoreGalleryImagesRequest $request, StoreGalleryImagesAction $action)
    {
        $validated = $request->validated();

        $action->handle(
            $request->file('images'),
            $validated['category'],
            $validated['caption'] ?? null,
        );

        return back()->with('success', 'تصاویر با موفقیت اضافه شدند.');
    }

    public function update(UpdateGalleryImageRequest $request, DoctorImage $image, UpdateGalleryImageAction $action)
    {
        $this->authorizeImage($image);

        $action->handle($image, $request->validated());

        return back()->with('success', 'تصویر بروزرسانی شد.');
    }

    public function destroy(DoctorImage $image, DeleteGalleryImageAction $action)
    {
        $this->authorizeImage($image);

        $action->handle($image);

        return back()->with('success', 'تصویر حذف شد.');
    }

    private function authorizeImage(DoctorImage $image): void
    {
        if ($image->doctor_id !== Auth::guard('doctor')->id()) {
            abort(403);
        }
    }
}
