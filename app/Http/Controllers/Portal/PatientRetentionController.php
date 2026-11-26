<?php
declare(strict_types=1);
namespace App\Http\Controllers\Portal;
use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Patient;
use App\Models\PatientFollowUpTask;
use App\Services\PatientRetention\RetentionSegment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
final class PatientRetentionController extends Controller
{
    use HasActiveDoctor;
    public function index(Request $request, RetentionSegment $segmenter, RecordSecurityAuditEventAction $audit)
    {
        $doctor=$this->currentDoctor();
        $this->authorizeSecretaryPermission('patients.view',$doctor);
        $clinicIds=$this->accessibleClinicIds($doctor);
        abort_if($clinicIds===[],403,'هیچ مطب فعالی برای بررسی در دسترس نیست.');
        $filters=$request->validate([
            'search'=>['nullable','string','max:120'],
            'segment'=>['nullable','in:all,win_back,attention,follow_up,scheduled,recent,unknown'],
            'inactive_days'=>['nullable','integer','min:30','max:730'],
        ]);
        $today=today();
        $threshold=(int)($filters['inactive_days']??90);
        $query=Patient::query()->where('patients.doctor_id',$doctor->id)
            ->whereHas('appointments',fn($q)=>$q->whereIn('clinic_id',$clinicIds)->whereNotIn('status',['cancelled','failed']))
            ->withCount(['appointments as appointment_count'=>fn($q)=>$q->whereIn('clinic_id',$clinicIds)->whereNotIn('status',['cancelled','failed'])])
            ->withMax(['appointments as last_appointment_at'=>fn($q)=>$q->whereIn('clinic_id',$clinicIds)->whereNotIn('status',['cancelled','failed'])],'appointment_date')
            ->withExists(['appointments as has_upcoming_appointment'=>fn($q)=>$q->whereIn('clinic_id',$clinicIds)->whereDate('appointment_date','>=',$today->toDateString())->whereNotIn('status',['cancelled','failed'])]);
        $search=trim((string)($filters['search']??''));
        if($search!=='') $query->where(fn($q)=>$q->where('name','like','%'.$search.'%')->orWhere('phone','like','%'.$search.'%'));
        $patients=$query->orderBy('name')->paginate(30)->withQueryString();
        $ids=$patients->getCollection()->pluck('id');
        $includeLegacyFollowUps=Auth::guard('web')->user()?->role !== 'secretary';
        $followUps=$ids->isEmpty()?collect():PatientFollowUpTask::query()
            ->forAccessibleClinics($doctor->id,$clinicIds,$includeLegacyFollowUps)
            ->whereIn('patient_id',$ids)
            ->whereIn('status',['pending','in_progress'])
            ->selectRaw('patient_id, COUNT(*) as aggregate')->groupBy('patient_id')->pluck('aggregate','patient_id');
        $rows=$patients->getCollection()->map(function(Patient $patient) use($segmenter,$today,$followUps,$threshold){
            $last=$patient->last_appointment_at?Carbon::parse($patient->last_appointment_at):null;
            $count=(int)$followUps->get($patient->id,0);
            return ['id'=>$patient->id,'name'=>$patient->name,'phone'=>$patient->phone,'appointment_count'=>(int)$patient->appointment_count,
                'last_appointment_at'=>$last?->toDateString(),'open_followups'=>$count,'has_upcoming_appointment'=>(bool)$patient->has_upcoming_appointment,
                ...$segmenter->classify($last,(bool)$patient->has_upcoming_appointment,$count>0,$today,$threshold)];
        });
        $selected=$filters['segment']??'all';
        if($selected!=='all') $rows=$rows->where('key',$selected)->values();
        $page=$patients->getCollection();
        $stats=['total'=>$patients->total(),'win_back'=>$rows->where('key','win_back')->count(),
            'open_followups'=>$followUps->sum(),'scheduled'=>$page->where('has_upcoming_appointment',true)->count()];
        $audit->handle('patient_retention.viewed',null,['doctor_id'=>$doctor->id,'clinic_count'=>count($clinicIds),'page_patient_count'=>$rows->count()]);
        return Inertia::render('Portal/PatientRetention/Index',[
            'doctor'=>$doctor,'patients'=>$rows,
            'pagination'=>['current_page'=>$patients->currentPage(),'last_page'=>$patients->lastPage(),'total'=>$patients->total()],
            'stats'=>$stats,'filters'=>['search'=>$search,'segment'=>$selected,'inactive_days'=>$threshold],
            'generatedAt'=>now()->toIso8601String(),
        ]);
    }
}
