<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicBusinessLead;
use App\Models\Appointment;
use App\Support\PatientIdentityNormalizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicBusinessLeadController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('marketing.view',$doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        $filters = $request->validate([
            'status'=>['nullable',Rule::in(['new','contacted','qualified','converted','lost'])],
            'clinic_id'=>['nullable','integer'],
        ]);
        $clinicId = isset($filters['clinic_id']) ? (int) $filters['clinic_id'] : null;
        if ($clinicId !== null) $this->authorizeClinicAccess($clinicId,$doctor);

        $leads = ClinicBusinessLead::query()->where('doctor_id',$doctor->id)
            ->where(fn($query)=>$query->whereNull('clinic_id')->orWhereIn('clinic_id',$clinicIds))
            ->when($clinicId!==null,fn($query)=>$query->where('clinic_id',$clinicId))
            ->when(!empty($filters['status']),fn($query)=>$query->where('status',$filters['status']))
            ->with('clinic:id,name')->latest()->paginate(20)->withQueryString()
            ->through(fn(ClinicBusinessLead $lead)=>[
                'id'=>$lead->id,'name'=>$lead->name,'phone_masked'=>$this->maskPhone($lead->phone),
                'source'=>$lead->source,'status'=>$lead->status,'clinic'=>$lead->clinic?->name,
                'campaign_id'=>$lead->campaign_id,'contact_consent'=>$lead->contact_consent,
                'next_follow_up_at'=>$lead->next_follow_up_at?->toIso8601String(),
                'converted_at'=>$lead->converted_at?->toIso8601String(),'notes'=>$lead->notes,
                'created_at'=>$lead->created_at?->toIso8601String(),
            ]);

        return Inertia::render('Portal/Marketing/Leads',[
            'doctor'=>$doctor,
            'clinics'=>Address::query()->where('doctor_id',$doctor->id)->whereIn('id',$clinicIds)->where('is_active',true)->get(['id','name']),
            'leads'=>$leads,
            'filters'=>['status'=>$filters['status']??'','clinic_id'=>$clinicId??''],
            'canManage'=>Auth::guard('web')->user()?->role==='doctor'||$this->secretaryCanManage($doctor),
        ]);
    }

    public function store(Request $request, PatientIdentityNormalizer $normalizer, RecordSecurityAuditEventAction $audit)
    {
        $doctor=$this->currentDoctor();
        $this->authorizeSecretaryPermission('marketing.manage',$doctor);
        $data=$request->validate([
            'name'=>['required','string','min:2','max:160'],
            'phone'=>['required','string','max:45'],
            'email'=>['nullable','email','max:180'],
            'clinic_id'=>['nullable','integer'],
            'campaign_id'=>['nullable','integer'],
            'source'=>['required',Rule::in(['social','sms','website','partner','walk_in','other'])],
            'contact_consent'=>['accepted'],
            'notes'=>['nullable','string','max:2000'],
            'next_follow_up_at'=>['nullable','date','after_or_equal:now'],
        ]);
        $phone=$normalizer->normalizePhone($data['phone']);
        abort_unless(preg_match('/^09\d{9}$/',$phone)===1,422,'شماره همراه معتبر نیست.');
        $clinicId=isset($data['clinic_id'])?(int)$data['clinic_id']:null;
        if($clinicId!==null)$this->authorizeClinicAccess($clinicId,$doctor);
        if(!empty($data['campaign_id'])) {
            abort_unless(DB::table('clinic_marketing_campaigns')->where('id',$data['campaign_id'])->where('doctor_id',$doctor->id)->exists(),404);
        }

        $lead=ClinicBusinessLead::query()->create([
            'doctor_id'=>$doctor->id,'clinic_id'=>$clinicId,'campaign_id'=>$data['campaign_id']??null,
            'created_by_user_id'=>(int)Auth::guard('web')->id(),'name'=>trim($data['name']),
            'phone'=>$phone,'email'=>$data['email']??null,'source'=>$data['source'],
            'status'=>'new','contact_consent'=>true,'consent_at'=>now(),
            'next_follow_up_at'=>$data['next_follow_up_at']??null,'notes'=>$data['notes']??null,
        ]);
        $audit->handle('marketing.lead.created',$lead,[
            'doctor_id'=>$doctor->id,'clinic_id'=>$clinicId,'source'=>$lead->source,
            'contact_consent'=>true,'changed_fields'=>['lead_created'],
        ]);
        return back()->with('success','سرنخ ارتباطی با ثبت رضایت تماس ذخیره شد.');
    }

    public function update(Request $request, ClinicBusinessLead $lead, RecordSecurityAuditEventAction $audit)
    {
        $doctor=$this->currentDoctor();
        $this->authorizeSecretaryPermission('marketing.manage',$doctor);
        abort_unless((int)$lead->doctor_id===(int)$doctor->id,404);
        if($lead->clinic_id)$this->authorizeClinicAccess((int)$lead->clinic_id,$doctor);
        $data=$request->validate([
            'status'=>['required',Rule::in(['new','contacted','qualified','converted','lost'])],
            'next_follow_up_at'=>['nullable','date','after_or_equal:now'],
            'notes'=>['nullable','string','max:2000'],
            'converted_appointment_id'=>['nullable','integer'],
        ]);
        if($data['status']==='converted') {
            abort_unless(!empty($data['converted_appointment_id']),422,'برای تبدیل سرنخ باید نوبت واقعی انتخاب شود.');
            $appointment=Appointment::query()->whereKey($data['converted_appointment_id'])
                ->where('doctor_id',$doctor->id)->whereIn('clinic_id',$this->accessibleClinicIds($doctor))
                ->where('patient_phone',$lead->phone)->firstOrFail();
        }

        DB::transaction(function()use($lead,$data):void{
            $locked=ClinicBusinessLead::query()->whereKey($lead->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status!=='converted'||$data['status']==='converted',409,'سرنخ تبدیل‌شده قابل بازگردانی به مرحله قبلی نیست.');
            $locked->update([
                'status'=>$data['status'],'next_follow_up_at'=>$data['next_follow_up_at']??null,
                'notes'=>$data['notes']??null,
                'converted_appointment_id'=>$data['status']==='converted'?(int)$data['converted_appointment_id']:null,
                'converted_at'=>$data['status']==='converted'?($locked->converted_at??now()):null,
            ]);
        },3);
        $audit->handle('marketing.lead.updated',$lead,[
            'doctor_id'=>$doctor->id,'status'=>$data['status'],'changed_fields'=>['lead_status','follow_up'],
        ]);
        return back()->with('success','وضعیت و پیگیری سرنخ به‌روزرسانی شد.');
    }

    private function maskPhone(?string $phone): ?string
    {
        if(!$phone)return null;
        return mb_substr($phone,0,4).'••••'.mb_substr($phone,-3);
    }

    private function secretaryCanManage($doctor): bool
    {
        $user=Auth::guard('web')->user();
        if($user?->role!=='secretary')return false;
        $assignment=$user->doctors()->where('doctors.id',$doctor->id)->first();
        $permissions=$assignment?->pivot?->permissions??[];
        if(is_string($permissions))$permissions=json_decode($permissions,true)?:[];
        return is_array($permissions)&&in_array('marketing.manage',$permissions,true);
    }
}
