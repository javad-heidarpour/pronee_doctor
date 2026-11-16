<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Clinic\DeleteClinicAction;
use App\Actions\Portal\Clinic\ListClinicsAction;
use App\Actions\Portal\Clinic\StoreClinicAction;
use App\Actions\Portal\Clinic\UpdateClinicAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Portal\ClinicRequest;
use App\Models\Address;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ClinicController extends Controller
{
    public function index(ListClinicsAction $action)
    {
        return Inertia::render('Portal/Clinics', $action->handle());
    }

    public function store(ClinicRequest $request, StoreClinicAction $action)
    {
        $action->handle($request->validated());

        return back()->with('success', 'مطب با موفقیت اضافه شد.');
    }

    public function update(ClinicRequest $request, Address $clinic, UpdateClinicAction $action)
    {
        $this->authorizeClinic($clinic);

        $action->handle($clinic, $request->validated());

        return back()->with('success', 'اطلاعات مطب بروزرسانی شد.');
    }

    public function destroy(Address $clinic, DeleteClinicAction $action)
    {
        $this->authorizeClinic($clinic);

        $action->handle($clinic);

        return back()->with('success', 'مطب حذف شد.');
    }

    private function authorizeClinic(Address $clinic): void
    {
        if ($clinic->doctor_id !== Auth::guard('doctor')->id()) {
            abort(403);
        }
    }
}
