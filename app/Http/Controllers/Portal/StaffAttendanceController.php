<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use Carbon\Carbon;
use App\Support\StaffAttendanceCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

final class StaffAttendanceController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        $this->authorizeSecretaryPermission('staff.attendance.view', $doctor);
        $month = $request->validate(['month' => ['nullable', 'date_format:Y-m']])['month'] ?? now()->format('Y-m');
        $periodStart = Carbon::parse($month . '-01')->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $organizations = $doctor->clinicOrganizations()->wherePivot('status', 'active')->get();
        $scopes = [];
        foreach ($organizations as $organization) {
            $membership = DB::table('clinic_organization_members')
                ->where('organization_id', $organization->id)
                ->where('user_id', $user->id)
                ->where('is_active', true)->first();
            $isOwner = (int) $organization->owner_user_id === (int) $user->id;
            if (!$isOwner && !$membership) continue;
            $allowedClinicIds = DB::table('clinic_organization_clinics')
                ->where('organization_id', $organization->id)->where('status', 'active')
                ->pluck('clinic_id')->map(fn ($id) => (int) $id)->all();
            if (!$isOwner) {
                $assigned = $membership->clinic_ids ? json_decode($membership->clinic_ids, true) : [];
                $allowedClinicIds = array_values(array_intersect($allowedClinicIds, array_map('intval', is_array($assigned) ? $assigned : [])));
            }
            if (!$allowedClinicIds) continue;
            $scopes[] = ['organization' => $organization, 'clinic_ids' => $allowedClinicIds, 'is_owner' => $isOwner];
        }
        abort_if(!$scopes, 403, 'برای این حساب، عضویت فعال در مجموعه درمانی و شعبه‌ای ثبت نشده است.');

        $organizationIds = collect($scopes)->pluck('organization.id')->all();
        $canManage = collect($scopes)->contains(fn ($s) => $s['is_owner']);
        $scopeFilter = function ($query, string $alias) use ($scopes, $user): void {
            $query->where(function ($outer) use ($scopes, $user, $alias): void {
                foreach ($scopes as $scope) {
                    $outer->orWhere(function ($inner) use ($scope, $user, $alias): void {
                        $inner->where($alias . '.organization_id', $scope['organization']->id)
                            ->whereIn($alias . '.clinic_id', $scope['clinic_ids']);
                        if (!$scope['is_owner']) $inner->where($alias . '.staff_user_id', $user->id);
                    });
                }
            });
        };
        $attendanceQuery = DB::table('clinic_staff_attendances as a')
            ->join('users as u', 'u.id', '=', 'a.staff_user_id')
            ->join('addresses as c', 'c.id', '=', 'a.clinic_id');
        $scopeFilter($attendanceQuery, 'a');
        $attendances = $attendanceQuery->whereBetween('a.work_date', [$month . '-01', Carbon::parse($month . '-01')->endOfMonth()->toDateString()])
            ->orderByDesc('a.work_date')->orderBy('u.name')
            ->get(['a.id','a.organization_id','a.clinic_id','a.staff_user_id','a.work_date','a.check_in_at','a.check_out_at','a.status','a.minutes_late','a.minutes_worked','a.notes','u.name as staff_name','u.family as staff_family','c.name as clinic_name'])
            ->map(function ($row) use ($scopes): object {
                $row->can_correct = collect($scopes)->contains(fn ($scope) => $scope['is_owner'] && (int) $scope['organization']->id === (int) $row->organization_id);
                return $row;
            });

        $schedules = DB::table('clinic_staff_work_schedules as s')
            ->join('users as u','u.id','=','s.staff_user_id')
            ->join('addresses as c','c.id','=','s.clinic_id');
        $scopeFilter($schedules, 's');
        $schedules = $schedules->where('s.is_active',true)->whereDate('s.effective_from','<=',today())
            ->where(fn($q)=>$q->whereNull('s.effective_until')->orWhereDate('s.effective_until','>=',today()))
            ->orderBy('s.weekday')->get(['s.id','s.organization_id','s.clinic_id','s.staff_user_id','s.weekday','s.start_time','s.end_time','s.break_minutes','s.grace_minutes','s.effective_from','s.effective_until','u.name as staff_name','u.family as staff_family','c.name as clinic_name']);

        $ownedOrganizationIds = collect($scopes)->filter(fn ($scope) => $scope['is_owner'])->pluck('organization.id')->all();
        $members = collect();
        if ($canManage && $ownedOrganizationIds) {
            $members = DB::table('clinic_organization_members as m')->join('users as u','u.id','=','m.user_id')
                ->whereIn('m.organization_id',$ownedOrganizationIds)->where('m.is_active',true)
                ->select('m.organization_id','m.user_id','u.name','u.family','m.clinic_ids')->orderBy('u.name')->get()
                ->filter(function($member) use ($scopes) {
                    $scope = collect($scopes)->first(fn ($item) => $item['is_owner'] && (int) $item['organization']->id === (int) $member->organization_id);
                    $assigned = $member->clinic_ids ? json_decode($member->clinic_ids,true) : [];
                    return $scope && (int) $member->user_id !== (int) $scope['organization']->owner_user_id
                        && array_intersect($scope['clinic_ids'], array_map('intval', is_array($assigned) ? $assigned : []));
                })->values();
        }

        $todayRows = DB::table('clinic_staff_attendances')->where('staff_user_id',$user->id)
            ->whereIn('organization_id',$organizationIds)->whereDate('work_date',now()->toDateString())->get();

        $leaveQuery = DB::table('clinic_staff_leave_requests as l')
            ->join('users as staff','staff.id','=','l.staff_user_id')
            ->join('addresses as c','c.id','=','l.clinic_id');
        $scopeFilter($leaveQuery, 'l');
        $leaveRequests = $leaveQuery->whereDate('l.start_date','<=',$periodEnd->toDateString())
            ->whereDate('l.end_date','>=',$periodStart->toDateString())
            ->orderByDesc('l.start_date')
            ->get(['l.id','l.organization_id','l.clinic_id','l.staff_user_id','l.leave_type','l.start_date','l.end_date','l.status','l.reason','l.decision_note','l.decided_at','staff.name as staff_name','staff.family as staff_family','c.name as clinic_name'])
            ->map(function($leave) use ($scopes): object {
                $leave->can_decide=collect($scopes)->contains(fn($scope)=>$scope['is_owner']&&(int)$scope['organization']->id===(int)$leave->organization_id);
                return $leave;
            });

        $holidays = DB::table('clinic_organization_holidays')
            ->whereIn('organization_id',$organizationIds)
            ->whereBetween('holiday_date',[$periodStart->toDateString(),$periodEnd->toDateString()])
            ->orderBy('holiday_date')->get(['id','organization_id','holiday_date','name'])
            ->map(function($holiday) use ($scopes): object {
                $holiday->can_delete=collect($scopes)->contains(fn($scope)=>$scope['is_owner']&&(int)$scope['organization']->id===(int)$holiday->organization_id);
                return $holiday;
            });

        $reportSchedules = DB::table('clinic_staff_work_schedules as s')
            ->join('users as u','u.id','=','s.staff_user_id')
            ->join('addresses as c','c.id','=','s.clinic_id');
        $scopeFilter($reportSchedules, 's');
        $reportSchedules = $reportSchedules->where('s.is_active',true)
            ->whereDate('s.effective_from','<=',$periodEnd->toDateString())
            ->where(fn($q)=>$q->whereNull('s.effective_until')->orWhereDate('s.effective_until','>=',$periodStart->toDateString()))
            ->get(['s.organization_id','s.clinic_id','s.staff_user_id','s.weekday','s.start_time','s.end_time','s.effective_from','s.effective_until','u.name as staff_name','u.family as staff_family','c.name as clinic_name']);

        $attendanceKeys = $attendances->filter(fn($row)=>!empty($row->check_in_at))
            ->mapWithKeys(fn($row)=>[$row->organization_id.'|'.$row->clinic_id.'|'.$row->staff_user_id.'|'.$row->work_date=>true]);
        $holidayKeys = $holidays->mapWithKeys(fn($row)=>[$row->organization_id.'|'.$row->holiday_date=>true]);
        $absenceRows = collect();
        $today = Carbon::now();
        foreach ($reportSchedules as $schedule) {
            $from = Carbon::parse(max($periodStart->toDateString(), $schedule->effective_from));
            $untilDate = min($periodEnd->toDateString(), $today->toDateString(), $schedule->effective_until ?: '9999-12-31');
            $until = Carbon::parse($untilDate);
            if ($from->greaterThan($until)) continue;
            for ($day=$from->copy(); $day->lessThanOrEqualTo($until); $day->addDay()) {
                if ($day->dayOfWeek !== (int)$schedule->weekday) continue;
                $date = $day->toDateString();
                $isHoliday=$holidayKeys->has($schedule->organization_id.'|'.$date);
                $hasLeave = $leaveRequests->contains(fn($leave)=>
                    (int)$leave->organization_id===(int)$schedule->organization_id
                    && (int)$leave->clinic_id===(int)$schedule->clinic_id
                    && (int)$leave->staff_user_id===(int)$schedule->staff_user_id
                    && in_array($leave->status,['pending','approved'],true)
                    && $date >= $leave->start_date && $date <= $leave->end_date
                );
                $key=$schedule->organization_id.'|'.$schedule->clinic_id.'|'.$schedule->staff_user_id.'|'.$date;
                $shiftEnd=Carbon::parse($date.' '.$schedule->end_time,$today->timezone);
                if (!StaffAttendanceCalculator::shouldCountAbsence($day,$shiftEnd,$today,$attendanceKeys->has($key),$isHoliday,$hasLeave)) continue;
                $absenceRows->push([
                    'organization_id'=>$schedule->organization_id,'clinic_id'=>$schedule->clinic_id,
                    'staff_user_id'=>$schedule->staff_user_id,'work_date'=>$date,
                    'staff_name'=>$schedule->staff_name,'staff_family'=>$schedule->staff_family,
                    'clinic_name'=>$schedule->clinic_name,
                ]);
            }
        }

        $auditQuery = DB::table('clinic_staff_attendance_events as e')
            ->leftJoin('users as actor','actor.id','=','e.actor_user_id')
            ->join('users as staff','staff.id','=','e.staff_user_id');
        $scopeFilter($auditQuery, 'e');
        $auditEvents = $auditQuery->orderByDesc('e.created_at')->limit(100)
            ->get(['e.id','e.attendance_id','e.event_type','e.old_values','e.new_values','e.reason','e.created_at','actor.name as actor_name','actor.family as actor_family','staff.name as staff_name','staff.family as staff_family']);
        $summary = [
            'days_recorded' => $attendances->count(),
            'late_days' => $attendances->where('minutes_late','>',0)->count(),
            'worked_minutes' => (int) $attendances->sum('minutes_worked'),
            'incomplete_days' => $attendances->whereNull('check_out_at')->count(),
            'absent_days' => $absenceRows->count(),
        ];

        return Inertia::render('Portal/StaffAttendance/Index', [
            'doctor'=>$doctor,
            'organizations'=>collect($scopes)->map(fn($s)=>['id'=>$s['organization']->id,'name'=>$s['organization']->name,'can_manage'=>$s['is_owner'],'clinics'=>DB::table('addresses')->whereIn('id',$s['clinic_ids'])->get(['id','name'])])->values(),
            'members'=>$members,
            'attendances'=>$attendances,
            'schedules'=>$schedules,
            'todayAttendances'=>$todayRows,
            'auditEvents'=>$auditEvents,
            'leaveRequests'=>$leaveRequests,
            'holidays'=>$holidays,
            'absenceRows'=>$absenceRows->values(),
            'summary'=>$summary,
            'month'=>$month,
            'currentUserId'=>(int)$user->id,
            'canManage'=>$canManage,
        ]);
    }

    public function storeSchedule(Request $request)
    {
        [$organization,$clinicIds] = $this->ownerOrganization((int)$request->input('organization_id'));
        $data = $request->validate([
            'clinic_id'=>['required','integer'],
            'staff_user_id'=>['required','integer'],
            'weekday'=>['required','integer','between:0,6'],
            'start_time'=>['required','date_format:H:i'],
            'end_time'=>['required','date_format:H:i','after:start_time'],
            'break_minutes'=>['nullable','integer','between:0,240'],
            'grace_minutes'=>['nullable','integer','between:0,120'],
            'effective_from'=>['required','date'],
            'effective_until'=>['nullable','date','after_or_equal:effective_from'],
        ]);
        abort_unless(in_array((int)$data['clinic_id'],$clinicIds,true),403);
        $member = DB::table('clinic_organization_members')->where('organization_id',$organization->id)
            ->where('user_id',$data['staff_user_id'])->where('is_active',true)->first();
        abort_unless($member,404,'کارمند در این مجموعه عضویت فعال ندارد.');
        $assigned = $member->clinic_ids ? json_decode($member->clinic_ids,true) : [];
        abort_unless(in_array((int)$data['clinic_id'],array_map('intval',is_array($assigned)?$assigned:[]),true),403,'کارمند به این شعبه تخصیص ندارد.');
        $overlap = DB::table('clinic_staff_work_schedules')
            ->where('organization_id',$organization->id)->where('clinic_id',$data['clinic_id'])
            ->where('staff_user_id',$data['staff_user_id'])->where('weekday',$data['weekday'])->where('is_active',true)
            ->whereDate('effective_from','<=',$data['effective_until'] ?? '9999-12-31')
            ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$data['effective_from']))
            ->exists();
        abort_if($overlap,422,'برای این کارمند در این روز و بازه اعتبار، برنامه کاری هم‌پوشان وجود دارد.');
        DB::table('clinic_staff_work_schedules')->insert([
            'organization_id'=>$organization->id,'clinic_id'=>$data['clinic_id'],'staff_user_id'=>$data['staff_user_id'],
            'weekday'=>$data['weekday'],'start_time'=>$data['start_time'],'end_time'=>$data['end_time'],
            'break_minutes'=>$data['break_minutes']??0,'grace_minutes'=>$data['grace_minutes']??10,
            'effective_from'=>$data['effective_from'],'effective_until'=>$data['effective_until']??null,
            'is_active'=>true,'created_by'=>Auth::guard('web')->id(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('success','برنامه کاری کارمند ثبت شد.');
    }

    public function checkIn(Request $request)
    {
        $this->authorizeSecretaryPermission('staff.attendance.view', $this->currentDoctor());
        $data=$request->validate(['organization_id'=>['required','integer'],'clinic_id'=>['required','integer']]);
        [$organization,$clinicIds]=$this->memberOrganization((int)$data['organization_id']);
        abort_unless(in_array((int)$data['clinic_id'],$clinicIds,true),403);
        $user=Auth::guard('web')->user();
        $now=Carbon::now($organization->timezone ?: 'Asia/Tehran');
        $date=$now->toDateString();
        DB::transaction(function()use($organization,$data,$user,$now,$date,$request):void{
            $row=DB::table('clinic_staff_attendances')->where('organization_id',$organization->id)
                ->where('clinic_id',$data['clinic_id'])->where('staff_user_id',$user->id)->where('work_date',$date)
                ->lockForUpdate()->first();
            abort_if($row && $row->check_in_at,409,'ورود امروز قبلاً ثبت شده است.');
            $schedule=DB::table('clinic_staff_work_schedules')->where('organization_id',$organization->id)
                ->where('clinic_id',$data['clinic_id'])->where('staff_user_id',$user->id)
                ->where('weekday',$now->dayOfWeek)->where('is_active',true)
                ->whereDate('effective_from','<=',$date)->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$date))
                ->orderByDesc('effective_from')->first();
            if(!$schedule){
                $replacement=DB::table('clinic_staff_shift_replacements')->where('organization_id',$organization->id)
                    ->where('clinic_id',$data['clinic_id'])->where('replacement_staff_user_id',$user->id)
                    ->whereDate('work_date',$date)->where('status','assigned')->first();
                if($replacement)$schedule=(object)['start_time'=>$replacement->start_time,'end_time'=>$replacement->end_time,'grace_minutes'=>10,'break_minutes'=>0];
            }
            $late=0;
            if($schedule){
                $expected=Carbon::parse($date.' '.$schedule->start_time,$now->timezone);
                $late=StaffAttendanceCalculator::lateMinutes($now,$expected,(int)$schedule->grace_minutes);
            }
            $values=['check_in_at'=>$now->toDateTimeString(),'status'=>$late>0?'late':'present','minutes_late'=>$late,'updated_at'=>now()];
            if($row) DB::table('clinic_staff_attendances')->where('id',$row->id)->update($values);
            else $id=DB::table('clinic_staff_attendances')->insertGetId($values+[
                'organization_id'=>$organization->id,'clinic_id'=>$data['clinic_id'],'staff_user_id'=>$user->id,
                'work_date'=>$date,'minutes_worked'=>0,'created_at'=>now(),
            ]);
            $attendanceId=$row->id??$id;
            $this->event($attendanceId,$organization->id,(int)$data['clinic_id'],$user->id,$user->id,'check_in',null,$values,null,$request);
        },3);
        return back()->with('success','ورود شما ثبت شد.');
    }

    public function checkOut(Request $request)
    {
        $this->authorizeSecretaryPermission('staff.attendance.view', $this->currentDoctor());
        $data=$request->validate(['organization_id'=>['required','integer'],'clinic_id'=>['required','integer']]);
        [$organization,$clinicIds]=$this->memberOrganization((int)$data['organization_id']);
        abort_unless(in_array((int)$data['clinic_id'],$clinicIds,true),403);
        $user=Auth::guard('web')->user();
        $now=Carbon::now($organization->timezone ?: 'Asia/Tehran');
        $date=$now->toDateString();
        DB::transaction(function()use($organization,$data,$user,$now,$date,$request):void{
            $row=DB::table('clinic_staff_attendances')->where('organization_id',$organization->id)
                ->where('clinic_id',$data['clinic_id'])->where('staff_user_id',$user->id)->where('work_date',$date)
                ->lockForUpdate()->first();
            abort_unless($row && $row->check_in_at,409,'ابتدا باید ورود خود را ثبت کنید.');
            abort_if($row->check_out_at,409,'خروج امروز قبلاً ثبت شده است.');
            $checkIn=Carbon::parse($row->check_in_at,$now->timezone);
            abort_if($now->lessThanOrEqualTo($checkIn),422,'زمان خروج باید بعد از زمان ورود باشد.');
            $schedule=DB::table('clinic_staff_work_schedules')->where('organization_id',$organization->id)
                ->where('clinic_id',$data['clinic_id'])->where('staff_user_id',$user->id)->where('weekday',$now->dayOfWeek)
                ->where('is_active',true)->whereDate('effective_from','<=',$date)
                ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$date))
                ->orderByDesc('effective_from')->first();
            if(!$schedule){
                $replacement=DB::table('clinic_staff_shift_replacements')->where('organization_id',$organization->id)
                    ->where('clinic_id',$data['clinic_id'])->where('replacement_staff_user_id',$user->id)
                    ->whereDate('work_date',$date)->where('status','assigned')->first();
                if($replacement)$schedule=(object)['start_time'=>$replacement->start_time,'end_time'=>$replacement->end_time,'grace_minutes'=>10,'break_minutes'=>0];
            }
            $worked=StaffAttendanceCalculator::workedMinutes($checkIn,$now,(int)($schedule->break_minutes??0));
            $values=['check_out_at'=>$now->toDateTimeString(),'minutes_worked'=>$worked,'status'=>'completed','updated_at'=>now()];
            DB::table('clinic_staff_attendances')->where('id',$row->id)->update($values);
            $this->event($row->id,$organization->id,(int)$data['clinic_id'],$user->id,$user->id,'check_out',['check_out_at'=>null],$values,null,$request);
        },3);
        return back()->with('success','خروج شما ثبت شد و مدت کار محاسبه شد.');
    }

    public function correct(Request $request,int $attendance)
    {
        $data=$request->validate([
            'check_in_at'=>['nullable','date_format:Y-m-d H:i:s'],
            'check_out_at'=>['nullable','date_format:Y-m-d H:i:s'],
            'reason'=>['required','string','min:8','max:500'],
        ]);
        $row=DB::table('clinic_staff_attendances')->where('id',$attendance)->first();
        abort_unless($row,404);
        [$organization,$clinicIds]=$this->ownerOrganization((int)$row->organization_id);
        abort_unless(in_array((int)$row->clinic_id,$clinicIds,true),403);
        DB::transaction(function()use($row,$data,$request):void{
            $locked=DB::table('clinic_staff_attendances')->where('id',$row->id)->lockForUpdate()->first();
            $old=['check_in_at'=>$locked->check_in_at,'check_out_at'=>$locked->check_out_at,'minutes_worked'=>$locked->minutes_worked,'minutes_late'=>$locked->minutes_late,'status'=>$locked->status];
            $in=$data['check_in_at']??null;$out=$data['check_out_at']??null;
            abort_if($out && !$in,422,'برای ثبت خروج باید زمان ورود نیز مشخص باشد.');
            if ($in) abort_unless(Carbon::parse($in)->toDateString()===$locked->work_date,422,'تاریخ ورود باید با روز کاری رکورد یکسان باشد.');
            if ($out) abort_unless(Carbon::parse($out)->toDateString()===$locked->work_date,422,'تاریخ خروج باید با روز کاری رکورد یکسان باشد.');
            abort_if($in && $out && Carbon::parse($out)->lessThanOrEqualTo(Carbon::parse($in)),422,'خروج باید پس از ورود باشد.');
            $schedule=DB::table('clinic_staff_work_schedules')->where('organization_id',$locked->organization_id)
                ->where('clinic_id',$locked->clinic_id)->where('staff_user_id',$locked->staff_user_id)
                ->where('weekday',Carbon::parse($locked->work_date)->dayOfWeek)->where('is_active',true)
                ->whereDate('effective_from','<=',$locked->work_date)
                ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$locked->work_date))
                ->orderByDesc('effective_from')->first();
            $late=0;
            if($in && $schedule){
                $expected=Carbon::parse($locked->work_date.' '.$schedule->start_time);
                $late=StaffAttendanceCalculator::lateMinutes(Carbon::parse($in),$expected,(int)$schedule->grace_minutes);
            }
            $worked=($in && $out)?StaffAttendanceCalculator::workedMinutes(Carbon::parse($in),Carbon::parse($out),(int)($schedule->break_minutes??0)):0;
            $new=['check_in_at'=>$in,'check_out_at'=>$out,'minutes_worked'=>$worked,'minutes_late'=>$late,'status'=>$out?'completed':($in?($late>0?'late':'corrected'):'incomplete'),'reviewed_by'=>Auth::guard('web')->id(),'updated_at'=>now()];
            DB::table('clinic_staff_attendances')->where('id',$row->id)->update($new);
            $this->event($row->id,$row->organization_id,$row->clinic_id,$row->staff_user_id,Auth::guard('web')->id(),'manager_correction',$old,$new,$data['reason'],$request);
        },3);
        return back()->with('success','سوابق حضور اصلاح شد و تغییرات در گزارش حسابرسی ثبت شد.');
    }


    public function storeLeave(Request $request)
    {
        $this->authorizeSecretaryPermission('staff.attendance.view', $this->currentDoctor());
        $data=$request->validate([
            'organization_id'=>['required','integer'],
            'clinic_id'=>['required','integer'],
            'leave_type'=>['required','in:annual,sick,unpaid,mission'],
            'start_date'=>['required','date'],
            'end_date'=>['required','date','after_or_equal:start_date'],
            'reason'=>['required','string','min:5','max:2000'],
        ]);
        abort_if($data['leave_type']==='annual' && substr($data['start_date'],0,4)!==substr($data['end_date'],0,4),422,'درخواست مرخصی استحقاقی باید در محدوده یک سال ثبت شود.');
        if($data['leave_type']==='sick'){
            abort_if(Carbon::parse($data['start_date'])->lt(today()->subDays(30)),422,'درخواست مرخصی استعلاجی فقط تا ۳۰ روز قبل قابل ثبت است.');
        }else{
            abort_if(Carbon::parse($data['start_date'])->lt(today()),422,'مرخصی استحقاقی، بدون حقوق و مأموریت باید از امروز به بعد ثبت شوند.');
        }
        [$organization,$clinicIds]=$this->memberOrganization((int)$data['organization_id']);
        abort_unless(in_array((int)$data['clinic_id'],$clinicIds,true),403);
        $user=Auth::guard('web')->user();
        $overlap=DB::table('clinic_staff_leave_requests')
            ->where('organization_id',$organization->id)->where('staff_user_id',$user->id)
            ->where('clinic_id',$data['clinic_id'])->whereIn('status',['pending','approved'])
            ->whereDate('start_date','<=',$data['end_date'])
            ->whereDate('end_date','>=',$data['start_date'])->exists();
        abort_if($overlap,422,'برای این بازه زمانی درخواست مرخصی فعال یا در انتظار بررسی دارید.');
        DB::table('clinic_staff_leave_requests')->insert([
            'organization_id'=>$organization->id,'clinic_id'=>$data['clinic_id'],'staff_user_id'=>$user->id,
            'leave_type'=>$data['leave_type'],'start_date'=>$data['start_date'],'end_date'=>$data['end_date'],
            'status'=>'pending','reason'=>$data['reason'],'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('success','درخواست مرخصی/مأموریت برای بررسی مدیر ثبت شد.');
    }

    public function decideLeave(Request $request,int $leave)
    {
        $data=$request->validate([
            'decision'=>['required','in:approved,rejected'],
            'decision_note'=>['nullable','string','max:1000'],
        ]);
        $row=DB::table('clinic_staff_leave_requests')->where('id',$leave)->first();
        abort_unless($row,404);
        [$organization,$clinicIds]=$this->ownerOrganization((int)$row->organization_id);
        abort_unless(in_array((int)$row->clinic_id,$clinicIds,true),403);
        DB::transaction(function()use($row,$data,$organization):void{
            $locked=DB::table('clinic_staff_leave_requests')->where('id',$row->id)->lockForUpdate()->first();
            abort_unless($locked->status==='pending',409,'این درخواست قبلاً بررسی شده است.');
            if($data['decision']==='approved' && $locked->leave_type==='annual'){
                $from=Carbon::parse($locked->start_date)->startOfDay();
                $until=Carbon::parse($locked->end_date)->startOfDay();
                $year=(int)$from->format('Y');
                $schedules=DB::table('clinic_staff_work_schedules')->where('organization_id',$organization->id)
                    ->where('clinic_id',$locked->clinic_id)->where('staff_user_id',$locked->staff_user_id)
                    ->where('is_active',true)->whereDate('effective_from','<=',$year.'-12-31')
                    ->where(fn($q)=>$q->whereNull('effective_until')->orWhereDate('effective_until','>=',$year.'-01-01'))
                    ->get(['weekday','effective_from','effective_until']);
                $requestedDays=0;
                $day=$from->copy();
                while($day->lessThanOrEqualTo($until)){
                    $date=$day->toDateString();
                    if($schedules->contains(fn($s)=>(int)$s->weekday===$day->dayOfWeek && $s->effective_from<=$date && (!$s->effective_until||$s->effective_until>=$date)))$requestedDays++;
                    $day->addDay();
                }
                abort_if($requestedDays<1,422,'برای این بازه، روز کاری برنامه‌ریزی‌شده‌ای جهت مصرف مرخصی استحقاقی وجود ندارد.');
                $balance=DB::table('clinic_staff_leave_balances')->where('organization_id',$organization->id)
                    ->where('staff_user_id',$locked->staff_user_id)->where('year',$year)->first();
                $contract=DB::table('clinic_staff_contracts')->where('organization_id',$organization->id)
                    ->where('clinic_id',$locked->clinic_id)->where('staff_user_id',$locked->staff_user_id)
                    ->where('is_active',true)->orderByDesc('effective_from')->first();
                $entitled=(float)($balance->entitled_days??$contract->annual_leave_days??0)
                    +(float)($balance->carry_over_days??0)+(float)($balance->adjustment_days??0);
                $approved=DB::table('clinic_staff_leave_requests')->where('organization_id',$organization->id)
                    ->where('clinic_id',$locked->clinic_id)->where('staff_user_id',$locked->staff_user_id)
                    ->where('leave_type','annual')->where('status','approved')
                    ->whereDate('start_date','<=',$year.'-12-31')->whereDate('end_date','>=',$year.'-01-01')->get(['start_date','end_date']);
                $usedDays=0;
                foreach($approved as $approvedLeave){
                    $leaveFrom=Carbon::parse(max($approvedLeave->start_date,$year.'-01-01'));
                    $leaveUntil=Carbon::parse(min($approvedLeave->end_date,$year.'-12-31'));
                    while($leaveFrom->lessThanOrEqualTo($leaveUntil)){
                        $date=$leaveFrom->toDateString();
                        if($schedules->contains(fn($s)=>(int)$s->weekday===$leaveFrom->dayOfWeek && $s->effective_from<=$date && (!$s->effective_until||$s->effective_until>=$date)))$usedDays++;
                        $leaveFrom->addDay();
                    }
                }
                abort_if($requestedDays+$usedDays>$entitled,422,'مانده مرخصی استحقاقی برای تأیید این درخواست کافی نیست.');
            }
            DB::table('clinic_staff_leave_requests')->where('id',$row->id)->update([
                'status'=>$data['decision'],'decision_note'=>$data['decision_note']??null,
                'decided_by'=>Auth::guard('web')->id(),'decided_at'=>now(),'updated_at'=>now(),
            ]);
        },3);
        return back()->with('success',$data['decision']==='approved'?'درخواست تأیید شد.':'درخواست رد شد.');
    }

    public function storeHoliday(Request $request)
    {
        [$organization,$clinicIds]=$this->ownerOrganization((int)$request->input('organization_id'));
        $data=$request->validate([
            'holiday_date'=>['required','date'],
            'name'=>['required','string','max:160'],
        ]);
        $exists=DB::table('clinic_organization_holidays')->where('organization_id',$organization->id)
            ->whereDate('holiday_date',$data['holiday_date'])->exists();
        abort_if($exists,409,'برای این تاریخ قبلاً تعطیلی ثبت شده است.');
        DB::table('clinic_organization_holidays')->insert([
            'organization_id'=>$organization->id,'holiday_date'=>$data['holiday_date'],'name'=>$data['name'],
            'created_by'=>Auth::guard('web')->id(),'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('success','تعطیلی مجموعه ثبت شد.');
    }

    public function deleteHoliday(int $holiday)
    {
        $row=DB::table('clinic_organization_holidays')->where('id',$holiday)->first();
        abort_unless($row,404);
        $this->ownerOrganization((int)$row->organization_id);
        DB::table('clinic_organization_holidays')->where('id',$holiday)->delete();
        return back()->with('success','تعطیلی ثبت‌شده حذف شد.');
    }

    private function memberOrganization(int $id): array
    {
        $doctor=$this->currentDoctor();$user=Auth::guard('web')->user();
        $organization=$doctor->clinicOrganizations()->wherePivot('status','active')->whereKey($id)->firstOrFail();
        $isOwner=(int)$organization->owner_user_id===(int)$user->id;
        $membership=DB::table('clinic_organization_members')->where('organization_id',$id)->where('user_id',$user->id)->where('is_active',true)->first();
        abort_unless($isOwner||$membership,403,'عضویت فعال در این مجموعه ندارید.');
        $clinicIds=DB::table('clinic_organization_clinics')->where('organization_id',$id)->where('status','active')->pluck('clinic_id')->map(fn($v)=>(int)$v)->all();
        if(!$isOwner){$assigned=$membership->clinic_ids?json_decode($membership->clinic_ids,true):[];$clinicIds=array_values(array_intersect($clinicIds,array_map('intval',is_array($assigned)?$assigned:[])));}
        return [$organization,$clinicIds,$isOwner,$membership];
    }

    private function ownerOrganization(int $id): array
    {
        [$organization,$clinicIds,$isOwner]=$this->memberOrganization($id);
        abort_unless($isOwner,403,'فقط مالک مجموعه می‌تواند برنامه کاری یا سوابق حضور را مدیریت کند.');
        return [$organization,$clinicIds];
    }

    private function event(int $attendanceId,int $organizationId,int $clinicId,int $staffId,?int $actorId,string $type,?array $old,?array $new,?string $reason,Request $request): void
    {
        DB::table('clinic_staff_attendance_events')->insert([
            'attendance_id'=>$attendanceId,'organization_id'=>$organizationId,'clinic_id'=>$clinicId,
            'staff_user_id'=>$staffId,'actor_user_id'=>$actorId,'event_type'=>$type,
            'old_values'=>$old?json_encode($old):null,'new_values'=>$new?json_encode($new):null,
            'reason'=>$reason,'ip_address'=>$request->ip(),'user_agent'=>substr((string)$request->userAgent(),0,500),'created_at'=>now(),
        ]);
    }
}
