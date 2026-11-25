<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Patient;
use App\Models\PatientCarePathway;
use App\Models\PatientCarePathwayStep;
use App\Services\PatientCarePathwayProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class PatientCarePathwayController extends Controller
{
    use HasActiveDoctor;

    public function index(RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        abort_unless(
            $this->secretaryHasPermission($doctor, 'patients.clinical.view') ||
            $this->secretaryHasPermission($doctor, 'patients.clinical.manage'),
            403,
            'مجوز مشاهده برنامه‌های مراقبتی برای شما فعال نیست.'
        );
        $canManageClinical = $this->secretaryHasPermission($doctor, 'patients.clinical.manage');
        $canActivatePathway = Auth::guard('web')->user()?->role !== 'secretary';
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403);
        $base = PatientCarePathway::query()->where('doctor_id',$doctor->id)->whereIn('clinic_id',$clinicIds);
        $pathways = (clone $base)->with(['patient:id,name','clinic:id,name','steps.assignee:id,name,family'])->latest()->paginate(20)->withQueryString();
        $patients = Patient::query()->where('doctor_id',$doctor->id)->whereHas('appointments',fn($q)=>$q->whereIn('clinic_id',$clinicIds))->orderBy('name')->get(['id','name']);
        $clinics = $doctor->address()->whereIn('addresses.id',$clinicIds)->orderBy('name')->get(['addresses.id','addresses.name']);
        $audit->handle('patient.care_pathways.listed', null, ['doctor_id' => $doctor->id]);

        return Inertia::render('Portal/CarePathways/Index', [
            'doctor'=>$doctor,'pathways'=>$pathways,'patients'=>$patients,'clinics'=>$clinics,
            'canManageClinical'=>$canManageClinical,
            'canActivatePathway'=>$canActivatePathway,
            'stats'=>['draft'=>(clone $base)->where('status','draft')->count(),'active'=>(clone $base)->where('status','active')->count(),'completed'=>(clone $base)->where('status','completed')->count()],
        ]);
    }

    public function store(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $data = $request->validate([
            'patient_id'=>['required','integer'],'clinic_id'=>['required','integer',Rule::in($clinicIds)],
            'title'=>['required','string','max:180'],'description'=>['nullable','string','max:4000'],
            'goal'=>['nullable','string','max:500'],'starts_at'=>['nullable','date'],
            'ends_at'=>['nullable','date','after_or_equal:starts_at'],
            'steps'=>['required','array','min:1','max:20'],
            'steps.*.title'=>['required','string','max:180'],'steps.*.description'=>['nullable','string','max:2000'],
            'steps.*.category'=>['required',Rule::in(['education','measurement','appointment','medication_adherence','lifestyle','general'])],
            'steps.*.due_at'=>['nullable','date'],
        ]);
        $patient = Patient::query()->whereKey($data['patient_id'])->where('doctor_id',$doctor->id)
            ->whereHas('appointments',fn($q)=>$q->where('clinic_id',$data['clinic_id']))->firstOrFail();
        $pathway = DB::transaction(function () use ($data,$doctor,$patient) {
            $item = PatientCarePathway::create([
                'doctor_id'=>$doctor->id,'clinic_id'=>$data['clinic_id'],'patient_id'=>$patient->id,
                'created_by'=>Auth::guard('web')->id(),'title'=>$data['title'],'description'=>$data['description'] ?? null,
                'goal'=>$data['goal'] ?? null,'status'=>'draft','starts_at'=>$data['starts_at'] ?? null,'ends_at'=>$data['ends_at'] ?? null,
            ]);
            foreach ($data['steps'] as $index=>$step) $item->steps()->create([
                'title'=>$step['title'],'description'=>$step['description'] ?? null,'category'=>$step['category'],
                'due_at'=>$step['due_at'] ?? null,'sort_order'=>$index,
            ]);
            return $item;
        });
        $audit->handle('patient.care_pathway.created',$pathway,['doctor_id'=>$doctor->id,'patient_id'=>$patient->id,'step_count'=>count($data['steps'])]);
        return back()->with('success','برنامه مراقبتی به‌صورت پیش‌نویس ایجاد شد؛ برای نمایش به بیمار باید پزشک آن را فعال کند.');
    }

    public function activate(int $pathway, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeDoctorOnly();
        $item = PatientCarePathway::query()->whereKey($pathway)->where('doctor_id',$doctor->id)
            ->whereIn('clinic_id',$this->accessibleClinicIds($doctor))->withCount('steps')->firstOrFail();
        abort_unless(app(PatientCarePathwayProgress::class)->canActivate($item->status, $item->steps_count), 422, 'فقط پیش‌نویس دارای گام قابل فعال‌سازی است.');
        $item->update(['status'=>'active','approved_by'=>Auth::guard('web')->id(),'activated_at'=>now()]);
        $audit->handle('patient.care_pathway.activated',$item,['doctor_id'=>$doctor->id]);
        return back()->with('success','برنامه مراقبتی پس از تأیید پزشک فعال شد.');
    }

    public function verifyStep(Request $request, int $step, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.clinical.manage', $doctor);
        $data = $request->validate(['verification_note' => ['nullable', 'string', 'max:1000']]);
        $clinicIds = $this->accessibleClinicIds($doctor);

        abort_if($clinicIds === [], 403);

        DB::transaction(function () use ($step, $doctor, $clinicIds, $data, $audit): void {
            // Lock the parent first so patient reports and staff verification
            // serialize on the same pathway before either step is changed.
            $pathway = PatientCarePathway::query()
                ->where('doctor_id', $doctor->id)
                ->whereIn('clinic_id', $clinicIds)
                ->where('status', 'active')
                ->whereHas('steps', fn ($query) => $query->whereKey($step))
                ->lockForUpdate()
                ->firstOrFail();

            $item = PatientCarePathwayStep::query()
                ->whereKey($step)
                ->where('pathway_id', $pathway->id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(
                $item->status === 'patient_reported',
                422,
                'این گام هنوز توسط بیمار گزارش نشده است.'
            );

            $item->update([
                'status' => 'verified',
                'verified_at' => now(),
                'verified_by' => Auth::guard('web')->id(),
                'verification_note' => $data['verification_note'] ?? null,
            ]);

            $steps = PatientCarePathwayStep::query()
                ->where('pathway_id', $pathway->id)
                ->orderBy('sort_order')
                ->lockForUpdate()
                ->get();

            if (app(PatientCarePathwayProgress::class)->isComplete($steps)) {
                $pathway->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
            }

            // Keep the clinical transition and its audit event atomic.
            $audit->handle('patient.care_pathway.step_verified', $item, [
                'pathway_id' => $pathway->id,
            ]);
        }, 3);

        return back()->with('success', 'گام برنامه بررسی و تأیید شد.');
    }

    private function secretaryHasPermission(\App\Models\Doctor $doctor, string $permission): bool
    {
        $user = Auth::guard('web')->user();

        if (!$user || $user->role !== 'secretary') {
            return true;
        }

        $assignment = $user->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];

        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return is_array($permissions) && in_array($permission, $permissions, true);
    }

}
