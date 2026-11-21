<?php
declare(strict_types=1);
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\TimeSlot;
use App\Services\Scheduling\ClinicCapacityAdvisor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
final class ClinicCapacityController extends Controller
{
    use HasActiveDoctor;
    public function index(Request $request,ClinicCapacityAdvisor $advisor): Response
    {
        $doctor=$this->currentDoctor();
        $this->authorizeSecretaryPermission('shifts.view',$doctor);
        $allowedClinicIds=$this->accessibleClinicIds($doctor);
        $filters=$request->validate(['clinic_id'=>['nullable','integer'],'days'=>['nullable','integer','min:14','max:90']]);
        $clinicId=isset($filters['clinic_id'])&&$filters['clinic_id']!==''?(int)$filters['clinic_id']:null;
        if ($clinicId!==null) abort_unless(in_array($clinicId,$allowedClinicIds,true),403,'شما به این مطب دسترسی ندارید.');
        $days=(int)($filters['days']??56); $from=now()->subDays($days-1)->toDateString(); $to=now()->subDay()->toDateString();
        $slots=TimeSlot::query()->where('doctor_id',$doctor->id)
            ->whereIn('clinic_id',$clinicId===null?$allowedClinicIds:[$clinicId])
            ->whereBetween('date',[$from,$to])
            ->with(['clinic:id,name,city_id','appointment:id,time_slot_id,status'])
            ->orderBy('date')->orderBy('start_time')
            ->get(['id','clinic_id','date','start_time','end_time','status'])
            ->map(static fn(TimeSlot $slot):array=>[
                'clinic_id'=>(int)$slot->clinic_id,'clinic_name'=>(string)($slot->clinic?->name??'مطب'),
                'date'=>$slot->date->format('Y-m-d'),'start_time'=>(string)$slot->start_time,
                'end_time'=>(string)$slot->end_time,'status'=>(string)$slot->status,
                'appointment_status'=>$slot->appointment?->status,
            ]);
        $clinics=$doctor->address()->whereIn('addresses.id',$allowedClinicIds)
            ->where('addresses.is_active',true)->orderBy('name')->get(['addresses.id','addresses.name']);
        return Inertia::render('Portal/Capacity/Index',[
            'doctor'=>$doctor,'clinics'=>$clinics,
            'filters'=>['clinic_id'=>$clinicId,'days'=>$days],
            'period'=>['from'=>$from,'to'=>$to],
            ...$advisor->summarize($slots),
        ]);
    }
}