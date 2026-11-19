<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\ClinicStaffPayroll;
use App\Services\Finance\ClinicAccountingService;
use App\Support\StaffPayrollCalculator;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class StaffManagementController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        $this->authorizeSecretaryPermission('staff.attendance.view', $doctor);
        abort_unless(in_array($user->role, ['doctor','admin'], true), 403, 'مدیریت قرارداد و حقوق فقط برای مالک مجموعه مجاز است.');

        $month = $request->validate(['month'=>['nullable','date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $start = Carbon::parse($month.'-01')->startOfDay();
        $end = $start->copy()->endOfMonth();
        $organizations = $doctor->clinicOrganizations()->wherePivot('status','active')
            ->where('owner_user_id',$user->id)->get();
        $organizationIds = $organizations->pluck('id')->map(fn($id)=>(int)$id)->all();
        abort_if(!$organizationIds,403,'مجموعه درمانی تحت مالکیت فعالی برای این حساب یافت نشد.');

        $members = DB::table('clinic_organization_members as m')
            ->join('users as u','u.id','=','m.user_id')
            ->whereIn('m.organization_id',$organizationIds)->where('m.is_active',true)
            ->orderBy('u.name')->get(['m.organization_id','m.user_id','m.role','m.clinic_ids','u.name','u.family']);

        $contracts = DB::table('clinic_staff_contracts as c')
            ->join('users as u','u.id','=','c.staff_user_id')
            ->join('addresses as a','a.id','=','c.clinic_id')
            ->whereIn('c.organization_id',$organizationIds)
            ->whereDate('c.effective_from','<=',$end->toDateString())
            ->where(fn($q)=>$q->whereNull('c.effective_until')->orWhereDate('c.effective_until','>=',$start->toDateString()))
            ->orderBy('u.name')->get(['c.*','u.name as staff_name','u.family as staff_family','a.name as clinic_name']);

        $payrolls = ClinicStaffPayroll::query()->with(['staff:id,name,family','clinic:id,name'])
            ->whereIn('organization_id',$organizationIds)
            ->whereDate('period_start','<=',$end->toDateString())
            ->whereDate('period_end','>=',$start->toDateString())
            ->orderByDesc('id')->get();

        $balanceRows = DB::table('clinic_staff_leave_balances as b')
            ->join('users as u','u.id','=','b.staff_user_id')
            ->whereIn('b.organization_id',$organizationIds)->where('b.year',(int)$start->format('Y'))
            ->get(['b.*','u.name as staff_name','u.family as staff_family']);
        $usedLeave = DB::table('clinic_staff_leave_requests')
            ->whereIn('organization_id',$organizationIds)->where('leave_type','annual')->where('status','approved')
            ->whereDate('start_date','<=',$end->toDateString())->whereDate('end_date','>=',$start->copy()->startOfYear()->toDateString())
            ->get(['organization_id','staff_user_id','clinic_id','start_date','end_date']);
        $leaveBalances = $balanceRows->map(function($balance) use ($usedLeave,$start,$end) {
            $used = $usedLeave->where('organization_id',$balance->organization_id)
                ->where('staff_user_id',$balance->staff_user_id)
                ->sum(fn($leave)=>$this->scheduledLeaveDays(
                    (int)$leave->organization_id,(int)$leave->staff_user_id,(int)$leave->clinic_id,
                    max($leave->start_date,$start->copy()->startOfYear()->toDateString()),
                    min($leave->end_date,$end->toDateString())
                ));
            $balance->used_days = $used;
            $balance->remaining_days = max(0,(float)$balance->entitled_days+(float)$balance->carry_over_days+(float)$balance->adjustment_days-$used);
            return $balance;
        });

        $attendance = DB::table('clinic_staff_attendances as a')
            ->join('users as u','u.id','=','a.staff_user_id')
            ->whereIn('a.organization_id',$organizationIds)->whereBetween('a.work_date',[$start->toDateString(),$end->toDateString()])
            ->select('a.organization_id','a.clinic_id','a.staff_user_id','a.work_date','a.check_in_at','a.check_out_at','a.minutes_late','a.minutes_worked','u.name','u.family')
            ->get();
        $performance = $attendance->groupBy(fn($row)=>$row->organization_id.'|'.$row->clinic_id.'|'.$row->staff_user_id)
            ->map(function($rows) {
                return [
                    'organization_id'=>$rows->first()->organization_id,
                    'clinic_id'=>$rows->first()->clinic_id,
                    'staff_user_id'=>$rows->first()->staff_user_id,
                    'name'=>$rows->first()->name,
                    'family'=>$rows->first()->family,
                    'recorded_days'=>$rows->count(),
                    'late_days'=>$rows->where('minutes_late','>',0)->count(),
                    'worked_minutes'=>(int)$rows->sum('minutes_worked'),
                    'incomplete_days'=>$rows->whereNull('check_out_at')->count(),
                    'late_minutes'=>(int)$rows->sum('minutes_late'),
                ];
            })->values();

        $clinics = DB::table('clinic_organization_clinics as oc')
            ->join('addresses as a','a.id','=','oc.clinic_id')
            ->whereIn('oc.organization_id',$organizationIds)->where('oc.status','active')
            ->get(['oc.organization_id','a.id','a.name']);

        $today=Carbon::now('Asia/Tehran');
        $weekday=$today->dayOfWeek;
        $todayDate=$today->toDateString();
        $todaySchedules=DB::table('clinic_staff_work_schedules as s')
            ->join('users as u','u.id','=','s.staff_user_id')
            ->whereIn('s.organization_id',$organizationIds)->where('s.weekday',$weekday)->where('s.is_active',true)
            ->whereDate('s.effective_from','<=',$todayDate)
            ->where(fn($q)=>$q->whereNull('s.effective_until')->orWhereDate('s.effective_until','>=',$todayDate))
            ->whereTime('s.start_time','<=',$today->format('H:i:s'))
            ->get(['s.organization_id','s.clinic_id','s.staff_user_id','s.start_time','s.end_time','u.name','u.family']);
        $coverageSuggestions=collect();
        foreach($todaySchedules as $shift){
            $present=DB::table('clinic_staff_attendances')->where('organization_id',$shift->organization_id)
                ->where('clinic_id',$shift->clinic_id)->where('staff_user_id',$shift->staff_user_id)
                ->whereDate('work_date',$todayDate)->whereNotNull('check_in_at')->exists();
            $pendingLeave=DB::table('clinic_staff_leave_requests')->where('organization_id',$shift->organization_id)
                ->where('clinic_id',$shift->clinic_id)->where('staff_user_id',$shift->staff_user_id)
                ->where('status','pending')->whereDate('start_date','<=',$todayDate)
                ->whereDate('end_date','>=',$todayDate)->exists();
            $holiday=DB::table('clinic_organization_holidays')->where('organization_id',$shift->organization_id)
                ->whereDate('holiday_date',$todayDate)->exists();
            $alreadyAssigned=DB::table('clinic_staff_shift_replacements')->where('organization_id',$shift->organization_id)
                ->where('clinic_id',$shift->clinic_id)->where('absent_staff_user_id',$shift->staff_user_id)
                ->whereDate('work_date',$todayDate)->where('status','assigned')->exists();
            if($present||$pendingLeave||$holiday||$alreadyAssigned)continue;
            $candidates=DB::table('clinic_organization_members as m')->join('users as u','u.id','=','m.user_id')
                ->where('m.organization_id',$shift->organization_id)->where('m.is_active',true)
                ->where('m.user_id','<>',$shift->staff_user_id)->whereJsonContains('m.clinic_ids',(int)$shift->clinic_id)
                ->get(['m.user_id','u.name','u.family'])
                ->filter(function($candidate)use($shift,$todayDate,$weekday){
                    $conflict=DB::table('clinic_staff_work_schedules')->where('organization_id',$shift->organization_id)
                        ->where('staff_user_id',$candidate->user_id)
                        ->where('weekday',$weekday)->where('is_active',true)
                        ->whereDate('effective_from','<=',$todayDate)
                        ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$todayDate))
                        ->where('start_time','<',$shift->end_time)->where('end_time','>',$shift->start_time)->exists();
                    $leave=DB::table('clinic_staff_leave_requests')->where('organization_id',$shift->organization_id)
                        ->where('staff_user_id',$candidate->user_id)->whereIn('status',['pending','approved'])
                        ->whereDate('start_date','<=',$todayDate)->whereDate('end_date','>=',$todayDate)->exists();
                    $alreadyWorking=DB::table('clinic_staff_attendances')->where('organization_id',$shift->organization_id)
                        ->where('staff_user_id',$candidate->user_id)->whereDate('work_date',$todayDate)
                        ->whereNotNull('check_in_at')->whereNull('check_out_at')->exists();
                    return !$conflict&&!$leave&&!$alreadyWorking;
                })->values();
            $coverageSuggestions->push([
                'organization_id'=>$shift->organization_id,'clinic_id'=>$shift->clinic_id,
                'absent_staff_user_id'=>$shift->staff_user_id,'staff_name'=>$shift->name,'staff_family'=>$shift->family,
                'work_date'=>$todayDate,'start_time'=>$shift->start_time,'end_time'=>$shift->end_time,
                'candidates'=>$candidates,
            ]);
        }

        return Inertia::render('Portal/StaffManagement/Index', [
            'doctor'=>$doctor,
            'organizations'=>$organizations->map(fn($o)=>['id'=>$o->id,'name'=>$o->name])->values(),
            'clinics'=>$clinics,
            'members'=>$members,
            'contracts'=>$contracts,
            'payrolls'=>$payrolls,
            'leaveBalances'=>$leaveBalances,
            'performance'=>$performance,
            'coverageSuggestions'=>$coverageSuggestions->values(),
            'month'=>$month,
            'currentYear'=>(int)$start->format('Y'),
        ]);
    }


    private function scheduledLeaveDays(int $organizationId,int $staffUserId,int $clinicId,string $from,string $until): int
    {
        $schedules=DB::table('clinic_staff_work_schedules')->where('organization_id',$organizationId)
            ->where('staff_user_id',$staffUserId)->where('clinic_id',$clinicId)->where('is_active',true)
            ->whereDate('effective_from','<=',$until)
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$from))
            ->get(['weekday','effective_from','effective_until']);
        if($schedules->isEmpty())return 0;
        $count=0;
        $day=Carbon::parse($from)->startOfDay();
        $last=Carbon::parse($until)->startOfDay();
        while($day->lessThanOrEqualTo($last)){
            $date=$day->toDateString();
            if($schedules->contains(fn($schedule)=>
                (int)$schedule->weekday===$day->dayOfWeek
                && $schedule->effective_from<=$date
                && (!$schedule->effective_until||$schedule->effective_until>=$date)
            ))$count++;
            $day->addDay();
        }
        return $count;
    }


    public function storeContract(Request $request)
    {
        $doctor=$this->currentDoctor();
        $this->authorizeSecretaryPermission('staff.attendance.view',$doctor);
        $user=Auth::guard('web')->user();
        $data=$request->validate([
            'organization_id'=>['required','integer'],
            'clinic_id'=>['required','integer'],
            'staff_user_id'=>['required','integer'],
            'compensation_type'=>['required',Rule::in(['monthly','hourly'])],
            'base_monthly_salary'=>['nullable','integer','min:0','max:999999999999'],
            'hourly_rate'=>['nullable','integer','min:0','max:999999999999'],
            'standard_monthly_minutes'=>['required','integer','between:60,30000'],
            'overtime_multiplier_basis_points'=>['required','integer','between:10000,30000'],
            'annual_leave_days'=>['required','numeric','between:0,60'],
            'paid_leave_types'=>['present','array'],
            'paid_leave_types.*'=>[Rule::in(['annual','sick','mission'])],
            'effective_from'=>['required','date'],
            'effective_until'=>['nullable','date','after_or_equal:effective_from'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        $organization=$doctor->clinicOrganizations()->where('owner_user_id',$user->id)
            ->wherePivot('status','active')->whereKey($data['organization_id'])->firstOrFail();
        $clinicId=(int)$data['clinic_id'];
        abort_unless($organization->clinics()->whereKey($clinicId)->wherePivot('status','active')->exists(),403);
        $member=$organization->members()->whereKey($data['staff_user_id'])->wherePivot('is_active',true)->firstOrFail();
        $assigned=$member->pivot->clinic_ids??[];
        if(is_string($assigned))$assigned=json_decode($assigned,true)?:[];
        abort_unless(in_array($clinicId,array_map('intval',(array)$assigned),true),403,'کارمند به شعبه انتخاب‌شده تخصیص ندارد.');
        if($data['compensation_type']==='monthly'){
            abort_if((int)($data['base_monthly_salary']??0)<1,422,'حقوق پایه ماهانه باید بیشتر از صفر باشد.');
        }else{
            abort_if((int)($data['hourly_rate']??0)<1,422,'نرخ ساعتی باید بیشتر از صفر باشد.');
        }
        $futureContract=DB::table('clinic_staff_contracts')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$member->id)->where('is_active',true)
            ->whereDate('effective_from','>=',$data['effective_from'])->exists();
        abort_if($futureContract,422,'قرارداد فعال دیگری با تاریخ شروع برابر یا دیرتر وجود دارد؛ ابتدا بازه آن را اصلاح کنید.');
        DB::table('clinic_staff_contracts')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$member->id)->where('is_active',true)
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$data['effective_from']))
            ->update(['is_active'=>false,'effective_until'=>Carbon::parse($data['effective_from'])->subDay()->toDateString(),'updated_at'=>now()]);
        DB::table('clinic_staff_contracts')->insert([
            'organization_id'=>$organization->id,'clinic_id'=>$clinicId,'staff_user_id'=>$member->id,
            'compensation_type'=>$data['compensation_type'],'base_monthly_salary'=>$data['base_monthly_salary']??0,
            'hourly_rate'=>$data['hourly_rate']??0,'standard_monthly_minutes'=>$data['standard_monthly_minutes'],
            'overtime_multiplier_basis_points'=>$data['overtime_multiplier_basis_points'],
            'annual_leave_days'=>$data['annual_leave_days'],'paid_leave_types'=>json_encode(array_values($data['paid_leave_types'])),
            'effective_from'=>$data['effective_from'],'effective_until'=>$data['effective_until']??null,
            'is_active'=>true,'notes'=>$data['notes']??null,'created_by'=>$user->id,'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('success','قرارداد جبران خدمات ثبت شد.');
    }

    public function setLeaveBalance(Request $request)
    {
        $doctor=$this->currentDoctor();
        $this->authorizeSecretaryPermission('staff.attendance.view',$doctor);
        $user=Auth::guard('web')->user();
        $data=$request->validate([
            'organization_id'=>['required','integer'],'staff_user_id'=>['required','integer'],
            'year'=>['required','integer','between:2020,2100'],
            'entitled_days'=>['required','numeric','between:0,60'],
            'carry_over_days'=>['nullable','numeric','between:0,60'],
            'adjustment_days'=>['nullable','numeric','between:-60,60'],
            'notes'=>['nullable','string','max:1000'],
        ]);
        $organization=$doctor->clinicOrganizations()->where('owner_user_id',$user->id)
            ->wherePivot('status','active')->whereKey($data['organization_id'])->firstOrFail();
        $organization->members()->whereKey($data['staff_user_id'])->wherePivot('is_active',true)->firstOrFail();
        DB::table('clinic_staff_leave_balances')->updateOrInsert(
            ['organization_id'=>$organization->id,'staff_user_id'=>$data['staff_user_id'],'year'=>$data['year']],
            ['entitled_days'=>$data['entitled_days'],'carry_over_days'=>$data['carry_over_days']??0,
             'adjustment_days'=>$data['adjustment_days']??0,'notes'=>$data['notes']??null,
             'updated_by'=>$user->id,'created_at'=>now(),'updated_at'=>now()]
        );
        return back()->with('success','سهمیه مرخصی سالانه ذخیره شد.');
    }

    public function createPayroll(Request $request)
    {
        $doctor=$this->currentDoctor();
        $this->authorizeSecretaryPermission('staff.attendance.view',$doctor);
        $user=Auth::guard('web')->user();
        $data=$request->validate([
            'organization_id'=>['required','integer'],'clinic_id'=>['required','integer'],
            'staff_user_id'=>['required','integer'],'period_start'=>['required','date'],
            'period_end'=>['required','date','after_or_equal:period_start'],
            'allowance_amount'=>['nullable','integer','min:0','max:999999999999'],
            'deduction_amount'=>['nullable','integer','min:0','max:999999999999'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        $organization=$doctor->clinicOrganizations()->where('owner_user_id',$user->id)
            ->wherePivot('status','active')->whereKey($data['organization_id'])->firstOrFail();
        $clinicId=(int)$data['clinic_id'];
        abort_unless($organization->clinics()->whereKey($clinicId)->wherePivot('status','active')->exists(),403);
        $organization->members()->whereKey($data['staff_user_id'])->wherePivot('is_active',true)->firstOrFail();
        $start=Carbon::parse($data['period_start'])->startOfDay();
        $end=Carbon::parse($data['period_end'])->endOfDay();
        $contract=DB::table('clinic_staff_contracts')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['staff_user_id'])
            ->whereDate('effective_from','<=',$start->toDateString())
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$end->toDateString()))
            ->orderByDesc('effective_from')->first();
        abort_unless($contract,422,'برای این کارمند در شعبه و بازه انتخابی قرارداد فعال وجود ندارد.');
        $existing=ClinicStaffPayroll::query()->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['staff_user_id'])->where('period_start',$start->toDateString())
            ->where('period_end',$end->toDateString())->first();
        abort_if($existing,409,'برای این کارمند و دوره حقوقی قبلاً فیش ایجاد شده است.');

        $schedules=DB::table('clinic_staff_work_schedules')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['staff_user_id'])->where('is_active',true)
            ->whereDate('effective_from','<=',$end->toDateString())
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$start->toDateString()))->get();
        $replacementShifts=DB::table('clinic_staff_shift_replacements')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('replacement_staff_user_id',$data['staff_user_id'])
            ->where('status','assigned')->whereBetween('work_date',[$start->toDateString(),$end->toDateString()])
            ->get()->map(function($shift){
                $shift->weekday=Carbon::parse($shift->work_date)->dayOfWeek;
                $shift->effective_from=$shift->work_date;
                $shift->effective_until=$shift->work_date;
                $shift->break_minutes=0;
                return $shift;
            });
        $schedules=$schedules->concat($replacementShifts);
        $attendances=DB::table('clinic_staff_attendances')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['staff_user_id'])
            ->whereBetween('work_date',[$start->toDateString(),$end->toDateString()])
            ->get()->keyBy('work_date');
        $leaves=DB::table('clinic_staff_leave_requests')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['staff_user_id'])->where('status','approved')
            ->whereDate('start_date','<=',$end->toDateString())->whereDate('end_date','>=',$start->toDateString())->get();
        $paidTypes=json_decode($contract->paid_leave_types??'[]',true)?:[];
        $worked=0;$scheduled=0;$paidLeave=0;$overtime=0;$unpaidLeaveDays=0;
        for($day=$start->copy();$day->startOfDay()->lessThanOrEqualTo($end);$day->addDay()){
            $dateValue=$day->toDateString();
            if($dateValue<$contract->effective_from || ($contract->effective_until && $dateValue>$contract->effective_until)) continue;
            $daySchedules=$schedules->where('weekday',$day->dayOfWeek)->filter(fn($s)=>
                $s->effective_from<=$day->toDateString()&&(!$s->effective_until||$s->effective_until>=$day->toDateString()));
            $dayScheduled=0;
            foreach($daySchedules as $shift){
                $shiftMinutes=(int)max(0,Carbon::parse($day->toDateString().' '.$shift->start_time)->diffInMinutes(Carbon::parse($day->toDateString().' '.$shift->end_time))-(int)$shift->break_minutes);
                $dayScheduled+=$shiftMinutes;
            }
            if($dayScheduled===0)continue;
            $scheduled+=$dayScheduled;
            $attendance=$attendances->get($day->toDateString());
            if($attendance && $attendance->check_in_at){
                $dayWorked=(int)$attendance->minutes_worked;
                $worked+=$dayWorked;
                $overtime+=max(0,$dayWorked-$dayScheduled);
                continue;
            }
            $leave=$leaves->first(fn($l)=>$l->start_date<=$day->toDateString()&&$l->end_date>=$day->toDateString());
            if($leave && in_array($leave->leave_type,$paidTypes,true))$paidLeave+=$dayScheduled;
            elseif($leave)$unpaidLeaveDays++;
        }
        abort_if($scheduled<1,422,'برای دوره حقوقی انتخاب‌شده، برنامه کاری معتبری برای این کارمند/شعبه وجود ندارد.');
        $calculation=StaffPayrollCalculator::calculate((array)$contract,$worked,$scheduled,$paidLeave,$overtime,
            (int)($data['allowance_amount']??0),(int)($data['deduction_amount']??0));
        $snapshot=$calculation+[
            'contract_id'=>$contract->id,'compensation_type'=>$contract->compensation_type,
            'period_start'=>$start->toDateString(),'period_end'=>$end->toDateString(),
            'unpaid_leave_days'=>$unpaidLeaveDays,'currency'=>'IRR','calculated_at'=>now()->toIso8601String(),
        ];
        ClinicStaffPayroll::query()->create([
            'organization_id'=>$organization->id,'clinic_id'=>$clinicId,'staff_user_id'=>$data['staff_user_id'],
            'period_start'=>$start->toDateString(),'period_end'=>$end->toDateString(),
            'gross_amount'=>$calculation['gross_amount'],'deduction_amount'=>$calculation['deduction_amount'],
            'net_amount'=>$calculation['net_amount'],'worked_minutes'=>$worked,'scheduled_minutes'=>$scheduled,
            'paid_leave_minutes'=>$paidLeave,'overtime_minutes'=>$overtime,'base_pay'=>$calculation['base_pay'],
            'overtime_pay'=>$calculation['overtime_pay'],'allowance_amount'=>$calculation['allowance_amount'],
            'calculation_snapshot'=>$snapshot,'status'=>'draft','notes'=>$data['notes']??null,
        ]);
        return back()->with('success','پیش‌نویس فیش حقوقی بر اساس قرارداد و سوابق ثبت شد؛ پیش از تأیید، جزئیات را بازبینی کنید.');
    }

    public function approvePayroll(int $payroll)
    {
        $user=Auth::guard('web')->user();
        DB::transaction(function()use($payroll,$user):void{
            $row=ClinicStaffPayroll::query()->whereKey($payroll)
                ->whereHas('organization',fn($q)=>$q->where('owner_user_id',$user->id))
                ->lockForUpdate()->firstOrFail();
            abort_unless($row->status==='draft',409,'فقط پیش‌نویس حقوق قابل تأیید است.');
            abort_if((int)$row->net_amount<0,422,'مبلغ خالص حقوق نامعتبر است.');
            $row->update(['status'=>'approved','approved_by'=>$user->id]);
        },3);
        return back()->with('success','فیش حقوقی تأیید شد.');
    }

    public function payPayroll(Request $request,int $payroll,ClinicAccountingService $accounting)
    {
        $user=Auth::guard('web')->user();
        $data=$request->validate([
            'payment_method'=>['required',Rule::in(['cash','card','transfer','online','cheque'])],
            'payment_reference'=>['nullable','string','max:120'],
        ]);
        DB::transaction(function()use($payroll,$user,$data,$accounting):void{
            $row=ClinicStaffPayroll::query()->whereKey($payroll)
                ->whereHas('organization',fn($q)=>$q->where('owner_user_id',$user->id))
                ->lockForUpdate()->firstOrFail();
            abort_unless($row->status==='approved',409,'فقط فیش تأییدشده قابل پرداخت است.');
            if((int)$row->net_amount>0){
                $accounting->post($row->organization_id,$row->clinic_id,null,(int)$user->id,
                    'clinic_staff_payroll',$row->id,'پرداخت حقوق کارکنان',now()->toDateTimeString(),
                    'staff-payroll:'.$row->id,
                    [['code'=>'5200','debit'=>(int)$row->net_amount,'credit'=>0],
                     ['code'=>'1000','debit'=>0,'credit'=>(int)$row->net_amount]]);
            }
            $row->update(['status'=>'paid','paid_by'=>$user->id,'paid_at'=>now(),
                'payment_method'=>$data['payment_method'],'payment_reference'=>$data['payment_reference']??null]);
        },3);
        return back()->with('success','حقوق پرداخت و در دفتر حسابداری ثبت شد.');
    }

    public function assignReplacement(Request $request)
    {
        $user=Auth::guard('web')->user();
        $data=$request->validate([
            'organization_id'=>['required','integer'],'clinic_id'=>['required','integer'],
            'absent_staff_user_id'=>['required','integer'],'replacement_staff_user_id'=>['required','integer'],
            'work_date'=>['required','date','after_or_equal:today'],'start_time'=>['required','date_format:H:i'],
            'end_time'=>['required','date_format:H:i','after:start_time'],'notes'=>['nullable','string','max:1000'],
        ]);
        $doctor=$this->currentDoctor();
        $organization=$doctor->clinicOrganizations()->where('owner_user_id',$user->id)
            ->wherePivot('status','active')->whereKey($data['organization_id'])->firstOrFail();
        $clinicId=(int)$data['clinic_id'];
        abort_unless($organization->clinics()->whereKey($clinicId)->wherePivot('status','active')->exists(),403);
        foreach(['absent_staff_user_id','replacement_staff_user_id'] as $field){
            $organization->members()->whereKey($data[$field])->wherePivot('is_active',true)->firstOrFail();
        }
        abort_if((int)$data['absent_staff_user_id']===(int)$data['replacement_staff_user_id'],422,'جانشین باید کارمند دیگری باشد.');
        $replacement=$organization->members()->whereKey($data['replacement_staff_user_id'])->firstOrFail();
        $assigned=$replacement->pivot->clinic_ids??[];
        if(is_string($assigned))$assigned=json_decode($assigned,true)?:[];
        abort_unless(in_array($clinicId,array_map('intval',(array)$assigned),true),403,'جانشین به این شعبه تخصیص ندارد.');
        $date=$data['work_date'];
        $weekday=Carbon::parse($date)->dayOfWeek;
        $absentShift=DB::table('clinic_staff_work_schedules')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['absent_staff_user_id'])
            ->where('weekday',$weekday)->where('is_active',true)->whereDate('effective_from','<=',$date)
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$date))
            ->whereTime('start_time','=',$data['start_time'])->whereTime('end_time','=',$data['end_time'])->first();
        abort_unless($absentShift,422,'شیفت کارمند غایب با بازه ارسالی تطابق ندارد.');
        $alreadyPresent=DB::table('clinic_staff_attendances')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['absent_staff_user_id'])
            ->whereDate('work_date',$date)->whereNotNull('check_in_at')->exists();
        abort_if($alreadyPresent,409,'برای کارمند اصلی ورود ثبت شده است؛ جانشین‌سازی این شیفت مجاز نیست.');
        $holiday=DB::table('clinic_organization_holidays')->where('organization_id',$organization->id)->whereDate('holiday_date',$date)->exists();
        abort_if($holiday,422,'این تاریخ در تقویم مجموعه تعطیل است.');
        $conflict=DB::table('clinic_staff_work_schedules')->where('organization_id',$organization->id)
            ->where('staff_user_id',$replacement->id)->where('weekday',$weekday)->where('is_active',true)
            ->whereDate('effective_from','<=',$date)->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$date))
            ->where('start_time','<',$data['end_time'])->where('end_time','>',$data['start_time'])->exists();
        abort_if($conflict,422,'جانشین در این بازه برنامه کاری هم‌پوشان دارد.');
        $onLeave=DB::table('clinic_staff_leave_requests')->where('organization_id',$organization->id)
            ->where('staff_user_id',$replacement->id)->whereIn('status',['pending','approved'])
            ->whereDate('start_date','<=',$date)->whereDate('end_date','>=',$date)->exists();
        abort_if($onLeave,422,'برای جانشین در این تاریخ درخواست مرخصی ثبت شده است.');
        $replacementAlreadyWorking=DB::table('clinic_staff_attendances')->where('organization_id',$organization->id)
            ->where('staff_user_id',$replacement->id)->whereDate('work_date',$date)
            ->whereNotNull('check_in_at')->whereNull('check_out_at')->exists();
        abort_if($replacementAlreadyWorking,422,'جانشین در حال حاضر در شیفت دیگری حضور ثبت‌شده دارد.');
        $replacementShiftConflict=DB::table('clinic_staff_shift_replacements')->where('organization_id',$organization->id)
            ->where('replacement_staff_user_id',$replacement->id)->whereDate('work_date',$date)->where('status','assigned')
            ->where('start_time','<',$data['end_time'])->where('end_time','>',$data['start_time'])->exists();
        abort_if($replacementShiftConflict,422,'جانشین برای این بازه به شیفت دیگری تخصیص یافته است.');
        DB::table('clinic_staff_shift_replacements')->updateOrInsert(
            ['organization_id'=>$organization->id,'clinic_id'=>$clinicId,'absent_staff_user_id'=>$data['absent_staff_user_id'],'work_date'=>$date],
            ['replacement_staff_user_id'=>$replacement->id,'start_time'=>$data['start_time'],'end_time'=>$data['end_time'],
             'status'=>'assigned','notes'=>$data['notes']??null,'created_by'=>$user->id,'created_at'=>now(),'updated_at'=>now()]
        );
        return back()->with('success','جانشین شیفت ثبت شد. این تخصیص جایگزین ثبت حضور و غیاب نیست؛ ورود/خروج واقعی باید جداگانه ثبت شود.');
    }
}
