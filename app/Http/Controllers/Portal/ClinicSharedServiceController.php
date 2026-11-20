<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicOrganization;
use App\Models\ClinicSharedService;
use App\Models\ClinicSharedServiceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicSharedServiceController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('appointments.view', $doctor);
        $user = Auth::guard('web')->user();
        $organizationIds = ClinicOrganization::query()
            ->where('owner_user_id', $user->id)->where('status', 'active')
            ->pluck('id')->map(fn ($id) => (int) $id)->all();
        $clinicIds = $this->accessibleClinicIds($doctor);

        $ownServices = ClinicSharedService::query()->whereIn('organization_id', $organizationIds)
            ->with(['organization:id,name','clinic:id,name'])->withCount('requests')->latest()->get();

        $availableServices = ClinicSharedService::query()->where('status', 'published')
            ->whereNotIn('organization_id', $organizationIds ?: [0])
            ->where(function ($query) {
                $query->whereNull('available_from')->orWhereDate('available_from', '<=', today());
            })
            ->where(function ($query) {
                $query->whereNull('available_until')->orWhereDate('available_until', '>=', today());
            })
            ->with(['organization:id,name','clinic:id,name'])->latest()->limit(100)->get();

        $requests = ClinicSharedServiceRequest::query()
            ->where(function ($query) use ($organizationIds): void {
                $query->whereIn('requesting_organization_id', $organizationIds ?: [0])
                    ->orWhereHas('service', fn ($service) => $service->whereIn('organization_id', $organizationIds ?: [0]));
            })
            ->with(['service.organization:id,name','requestingOrganization:id,name'])
            ->latest()->limit(100)->get();

        return Inertia::render('Portal/SharedServices/Index', [
            'doctor' => $doctor,
            'organizations' => ClinicOrganization::query()->whereIn('id', $organizationIds)->get(['id','name']),
            'clinics' => Address::query()->where('doctor_id',$doctor->id)->whereIn('id',$clinicIds)->where('is_active',true)->get(['id','name']),
            'ownServices' => $ownServices,
            'availableServices' => $availableServices,
            'requests' => $requests,
        ]);
    }

    public function store(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'organization_id' => ['required','integer'],
            'clinic_id' => ['required','integer'],
            'title' => ['required','string','min:3','max:160'],
            'category' => ['required','string','max:60'],
            'description' => ['required','string','min:10','max:3000'],
            'price_rial' => ['required','integer','min:0','max:1000000000000'],
            'weekly_capacity' => ['required','integer','between:1,500'],
            'available_from' => ['nullable','date'],
            'available_until' => ['nullable','date','after_or_equal:available_from'],
        ]);
        $organization = $this->ownedOrganization((int) $data['organization_id']);
        $clinicId = (int) $data['clinic_id'];
        $this->assertOrganizationClinic($organization, $doctor, $clinicId);

        $service = ClinicSharedService::query()->create([
            ...$data,
            'organization_id' => $organization->id,
            'doctor_id' => $doctor->id,
            'clinic_id' => $clinicId,
            'created_by_user_id' => (int) Auth::guard('web')->id(),
            'status' => 'published',
        ]);
        $audit->handle('network.shared_service.published', $service, [
            'doctor_id' => $doctor->id, 'organization_id' => $organization->id,
            'clinic_id' => $clinicId, 'changed_fields' => ['shared_service_published'],
        ]);

        return back()->with('success','خدمت مشترک منتشر شد و برای مراکز دیگر قابل درخواست است.');
    }

    public function requestService(Request $request, ClinicSharedService $service, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'organization_id' => ['required','integer'],
            'clinic_id' => ['required','integer'],
            'requested_capacity' => ['required','integer','between:1,500'],
            'request_note' => ['nullable','string','max:2000'],
        ]);
        abort_unless($service->status === 'published',404);
        $organization = $this->ownedOrganization((int) $data['organization_id']);
        abort_if((int) $service->organization_id === (int) $organization->id,422,'درخواست خدمت از سازمان خودتان امکان‌پذیر نیست.');
        $clinicId = (int) $data['clinic_id'];
        $this->assertOrganizationClinic($organization,$doctor,$clinicId);

        $requestRecord = DB::transaction(function () use ($service,$organization,$doctor,$clinicId,$data): ClinicSharedServiceRequest {
            $lockedService = ClinicSharedService::query()->whereKey($service->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedService->status === 'published',409);
            $existing = ClinicSharedServiceRequest::query()->where('shared_service_id',$lockedService->id)
                ->where('requesting_organization_id',$organization->id)->where('status','pending')->first();
            abort_if($existing,409,'برای این خدمت درخواست در انتظار بررسی دارید.');

            return ClinicSharedServiceRequest::query()->create([
                'shared_service_id'=>$lockedService->id,
                'requesting_organization_id'=>$organization->id,
                'requesting_doctor_id'=>$doctor->id,
                'requesting_clinic_id'=>$clinicId,
                'requested_by_user_id'=>(int) Auth::guard('web')->id(),
                'requested_capacity'=>$data['requested_capacity'],
                'status'=>'pending',
                'request_note'=>$data['request_note'] ?? null,
            ]);
        },3);

        $audit->handle('network.shared_service.requested',$requestRecord,[
            'doctor_id'=>$doctor->id,'requesting_organization_id'=>$organization->id,
            'shared_service_id'=>$service->id,'changed_fields'=>['request_created'],
        ]);
        return back()->with('success','درخواست همکاری برای ارائه خدمت ارسال شد.');
    }

    public function respond(Request $request, ClinicSharedServiceRequest $serviceRequest, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $data = $request->validate([
            'decision'=>['required',Rule::in(['accept','reject'])],
            'response_note'=>['nullable','string','max:2000'],
        ]);

        DB::transaction(function () use ($serviceRequest,$doctor,$data): void {
            $locked = ClinicSharedServiceRequest::query()->whereKey($serviceRequest->id)
                ->with('service')->lockForUpdate()->firstOrFail();
            $organization = ClinicOrganization::query()->whereKey($locked->service->organization_id)
                ->where('owner_user_id',Auth::guard('web')->id())->where('status','active')->first();
            abort_unless($organization,403);
            $this->assertDoctorOrganizationMembership($organization,$doctor);
            abort_unless($locked->status === 'pending',409,'این درخواست قبلاً تعیین تکلیف شده است.');
            $locked->update([
                'status'=>$data['decision']==='accept'?'accepted':'rejected',
                'response_note'=>$data['response_note'] ?? null,
                'responded_by_user_id'=>(int) Auth::guard('web')->id(),
                'responded_at'=>now(),
            ]);
        },3);

        $audit->handle('network.shared_service.responded',$serviceRequest,[
            'doctor_id'=>$doctor->id,'decision'=>$data['decision'],'changed_fields'=>['request_status'],
        ]);
        return back()->with('success',$data['decision']==='accept'?'درخواست خدمت پذیرفته شد.':'درخواست خدمت رد شد.');
    }

    private function ownedOrganization(int $organizationId): ClinicOrganization
    {
        $organization = ClinicOrganization::query()->whereKey($organizationId)
            ->where('owner_user_id',Auth::guard('web')->id())->where('status','active')->firstOrFail();
        $this->assertDoctorOrganizationMembership($organization,$this->currentDoctor());
        return $organization;
    }

    private function assertDoctorOrganizationMembership(ClinicOrganization $organization,$doctor): void
    {
        abort_unless(DB::table('clinic_organization_doctors')
            ->where('organization_id',$organization->id)->where('doctor_id',$doctor->id)
            ->where('status','active')->exists(),403,'پزشک فعال عضو این سازمان نیست.');
    }

    private function assertOrganizationClinic(ClinicOrganization $organization,$doctor,int $clinicId): void
    {
        $this->assertDoctorOrganizationMembership($organization,$doctor);
        abort_unless(in_array($clinicId,$this->accessibleClinicIds($doctor),true),403);
        abort_unless(DB::table('clinic_organization_clinics')
            ->where('organization_id',$organization->id)->where('clinic_id',$clinicId)
            ->where('status','active')->exists(),403,'شعبه به سازمان انتخاب‌شده تعلق ندارد.');
    }
}
