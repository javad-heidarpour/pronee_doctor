<?php

namespace App\Http\Controllers\Portal;

use App\Actions\Portal\Clinic\DeleteClinicAction;
use App\Actions\Portal\Clinic\ListClinicsAction;
use App\Actions\Portal\Clinic\StoreClinicAction;
use App\Actions\Portal\Clinic\UpdateClinicAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Http\Requests\Portal\ClinicRequest;
use App\Models\Address;
use Inertia\Inertia;

class ClinicController extends Controller
{
    use HasActiveDoctor;

    public function index(ListClinicsAction $action)
    {
        $doctor = $this->currentDoctor();

        return Inertia::render('Portal/Clinics', $action->handle($doctor, $this->accessibleClinicIds($doctor)));
    }

    public function store(ClinicRequest $request, StoreClinicAction $action)
    {
        $doctor = $this->currentDoctor();

        abort_if(auth('web')->user()?->role === 'secretary', 403, 'منشی اجازه ایجاد مطب جدید را ندارد.');
        $action->handle($doctor, $request->validated());

        return back()->with('success', 'مطب با موفقیت اضافه شد.');
    }

    public function update(
        ClinicRequest $request,
        Address $clinic,
        UpdateClinicAction $action
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeClinicAccess((int) $clinic->id, $doctor);

        $action->handle($clinic, $request->validated());

        return back()->with('success', 'اطلاعات مطب بروزرسانی شد.');
    }

    public function destroy(Address $clinic, DeleteClinicAction $action)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeClinic($clinic, $doctor);

        $action->handle($clinic);

        return back()->with('success', 'مطب حذف شد.');
    }

}
