<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Doctor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class StaffPerformanceReviewController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);

        $scopes = [];
        foreach ($doctor->clinicOrganizations()->wherePivot('status', 'active')
            ->where('clinic_organizations.status', 'active')->get() as $organization) {
            $owner = (int) $organization->owner_user_id === (int) $user->id;
            $member = DB::table('clinic_organization_members')->where('organization_id', $organization->id)
                ->where('user_id', $user->id)->where('is_active', true)->first();
            $clinicIds = DB::table('clinic_organization_clinics')->where('organization_id', $organization->id)
                ->where('status', 'active')->pluck('clinic_id')->map(fn ($id) => (int) $id)->all();
            if (!$owner && $member) {
                $assigned = is_array($member->clinic_ids) ? $member->clinic_ids : (json_decode((string) $member->clinic_ids, true) ?: []);
                $clinicIds = array_values(array_intersect($clinicIds, array_map('intval', (array) $assigned)));
            } elseif (!$owner) {
                $clinicIds = DB::table('addresses')->whereIn('id', $clinicIds)->where('doctor_id', $doctor->id)
                    ->pluck('id')->map(fn ($id) => (int) $id)->all();
            }
            if ($clinicIds !== []) $scopes[] = ['organization_id' => (int) $organization->id, 'clinic_ids' => $clinicIds, 'can_manage' => $owner];
        }
        abort_if($scopes === [], 403, 'عضویت فعال در مجموعه و شعبه‌ای برای ارزیابی عملکرد وجود ندارد.');

        $query = DB::table('clinic_staff_performance_reviews as r')
            ->join('users as staff', 'staff.id', '=', 'r.staff_user_id')
            ->leftJoin('users as reviewer', 'reviewer.id', '=', 'r.reviewer_user_id')
            ->join('addresses as clinic', 'clinic.id', '=', 'r.clinic_id')
            ->where(function ($outer) use ($scopes, $user): void {
                foreach ($scopes as $scope) {
                    $outer->orWhere(function ($inner) use ($scope, $user): void {
                        $inner->where('r.organization_id', $scope['organization_id'])
                            ->whereIn('r.clinic_id', $scope['clinic_ids']);
                        if (!$scope['can_manage']) $inner->where('r.staff_user_id', $user->id);
                    });
                }
            });
        $reviews = $query->orderByDesc('r.period_end')->orderByDesc('r.id')->limit(100)->get([
            'r.id','r.organization_id','r.clinic_id','r.staff_user_id','r.reviewer_user_id',
            'r.period_start','r.period_end','r.reliability_score','r.task_completion_score',
            'r.teamwork_score','r.communication_score','r.learning_score','r.strengths',
            'r.improvement_areas','r.goals','r.reviewer_notes','r.status','r.acknowledged_at',
            'r.employee_response','r.created_at','staff.name as staff_name','staff.family as staff_family',
            'reviewer.name as reviewer_name','reviewer.family as reviewer_family','clinic.name as clinic_name',
        ]);

        $organizations = collect($scopes)->map(function ($scope) {
            $org = DB::table('clinic_organizations')->where('id', $scope['organization_id'])->first();
            return ['id' => $scope['organization_id'], 'name' => $org?->name, 'can_manage' => $scope['can_manage'],
                'clinics' => DB::table('addresses')->whereIn('id', $scope['clinic_ids'])->orderBy('name')->get(['id','name'])];
        })->values();

        $staffOptions = collect($scopes)->filter(fn ($scope) => $scope['can_manage'])->flatMap(function ($scope) {
            $members = DB::table('clinic_organization_members as m')->join('users as u','u.id','=','m.user_id')
                ->where('m.organization_id',$scope['organization_id'])->where('m.is_active',true)
                ->select('u.id','u.name','u.family')->get()->each(fn ($u) => $u->organization_id = $scope['organization_id']);
            $doctors = DB::table('clinic_organization_doctors as od')->join('doctors as d','d.id','=','od.doctor_id')
                ->join('users as u','u.id','=','d.user_id')->where('od.organization_id',$scope['organization_id'])
                ->where('od.status','active')->select('u.id','u.name','u.family')->get()->each(fn ($u) => $u->organization_id = $scope['organization_id']);
            return $members->concat($doctors);
        })->unique('id')->map(fn ($u) => ['id'=>(int)$u->id,'label'=>trim(($u->name??'').' '.($u->family??'')),'organization_id'=>(int)$u->organization_id])->values();

        return Inertia::render('Portal/StaffPerformance/Index', [
            'doctor'=>$doctor,'organizations'=>$organizations,'staffOptions'=>$staffOptions,
            'reviews'=>$reviews,'canManage'=>collect($scopes)->contains(fn($s)=>$s['can_manage']),
            'currentUserId'=>(int)$user->id,
        ]);
    }

    public function store(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $data = $request->validate([
            'organization_id'=>['required','integer'],
            'clinic_id'=>['required','integer'],
            'staff_user_id'=>['required','integer','exists:users,id'],
            'period_start'=>['required','date'],
            'period_end'=>['required','date','after_or_equal:period_start'],
            'reliability_score'=>['required','integer','between:1,5'],
            'task_completion_score'=>['required','integer','between:1,5'],
            'teamwork_score'=>['required','integer','between:1,5'],
            'communication_score'=>['required','integer','between:1,5'],
            'learning_score'=>['required','integer','between:1,5'],
            'strengths'=>['nullable','string','max:2000'],
            'improvement_areas'=>['nullable','string','max:2000'],
            'goals'=>['nullable','string','max:2000'],
            'reviewer_notes'=>['nullable','string','max:2000'],
        ]);
        $organization = $doctor->clinicOrganizations()->wherePivot('status','active')
            ->where('clinic_organizations.status','active')->where('owner_user_id',$user->id)
            ->whereKey((int)$data['organization_id'])->firstOrFail();
        $clinicId = (int)$data['clinic_id'];
        abort_unless($organization->clinics()->whereKey($clinicId)->wherePivot('status','active')->exists(),403);
        $this->assertStaffAssignment($organization->id,(int)$data['staff_user_id'],$clinicId);
        abort_if(DB::table('clinic_staff_performance_reviews')->where('organization_id',$organization->id)
            ->where('clinic_id',$clinicId)->where('staff_user_id',$data['staff_user_id'])
            ->whereDate('period_start',$data['period_start'])->whereDate('period_end',$data['period_end'])->exists(),
            409,'برای این کارمند و بازه زمانی قبلاً ارزیابی ثبت شده است.');

        $id = DB::table('clinic_staff_performance_reviews')->insertGetId([
            'organization_id'=>$organization->id,'clinic_id'=>$clinicId,'staff_user_id'=>(int)$data['staff_user_id'],
            'reviewer_user_id'=>(int)$user->id,'period_start'=>$data['period_start'],'period_end'=>$data['period_end'],
            'reliability_score'=>$data['reliability_score'],'task_completion_score'=>$data['task_completion_score'],
            'teamwork_score'=>$data['teamwork_score'],'communication_score'=>$data['communication_score'],
            'learning_score'=>$data['learning_score'],'strengths'=>$data['strengths']??null,
            'improvement_areas'=>$data['improvement_areas']??null,'goals'=>$data['goals']??null,
            'reviewer_notes'=>$data['reviewer_notes']??null,'status'=>'draft',
            'created_at'=>now(),'updated_at'=>now(),
        ]);
        $audit->handle('staff.performance_review.created',(object)['id'=>$id],[
            'organization_id'=>$organization->id,'clinic_id'=>$clinicId,
            'staff_user_id'=>(int)$data['staff_user_id'],'period_start'=>$data['period_start'],
            'period_end'=>$data['period_end'],'changed_fields'=>['performance_review_created'],
        ]);
        return back()->with('success','پیش‌نویس ارزیابی عملکرد ثبت شد؛ پس از بازبینی آن را منتشر کنید.');
    }

    public function publish(int $review, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user,401);
        $row = DB::table('clinic_staff_performance_reviews as r')
            ->join('clinic_organizations as o','o.id','=','r.organization_id')
            ->where('r.id',$review)->where('o.owner_user_id',$user->id)
            ->select('r.*')->first();
        abort_unless($row,404);
        abort_unless($this->currentDoctor()->clinicOrganizations()->whereKey($row->organization_id)->exists(),404);
        abort_unless($row->status==='draft',409,'فقط پیش‌نویس قابل انتشار است.');
        DB::table('clinic_staff_performance_reviews')->where('id',$review)->where('status','draft')->update([
            'status'=>'published','published_at'=>now(),'updated_at'=>now(),
        ]);
        $audit->handle('staff.performance_review.published',(object)['id'=>$review],[
            'organization_id'=>(int)$row->organization_id,'clinic_id'=>(int)$row->clinic_id,
            'staff_user_id'=>(int)$row->staff_user_id,'changed_fields'=>['status'],
        ]);
        return back()->with('success','ارزیابی برای کارمند منتشر شد.');
    }

    public function acknowledge(Request $request, int $review, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user,401);
        $data = $request->validate(['employee_response'=>['nullable','string','max:2000']]);
        $row = DB::table('clinic_staff_performance_reviews')->where('id',$review)
            ->where('staff_user_id',$user->id)->where('status','published')->first();
        abort_unless($row,404);
        DB::table('clinic_staff_performance_reviews')->where('id',$review)->update([
            'acknowledged_at'=>$row->acknowledged_at ?: now(),
            'employee_response'=>$data['employee_response']??null,'updated_at'=>now(),
        ]);
        $audit->handle('staff.performance_review.acknowledged',(object)['id'=>$review],[
            'organization_id'=>(int)$row->organization_id,'clinic_id'=>(int)$row->clinic_id,
            'staff_user_id'=>(int)$user->id,'changed_fields'=>['acknowledged_at','employee_response'],
        ]);
        return back()->with('success','دریافت ارزیابی ثبت شد؛ این تأیید به معنی موافقت با محتوای آن نیست.');
    }

    private function assertStaffAssignment(int $organizationId,int $staffUserId,int $clinicId): void
    {
        $member = DB::table('clinic_organization_members')->where('organization_id',$organizationId)
            ->where('user_id',$staffUserId)->where('is_active',true)->first();
        if ($member) {
            $assigned = is_array($member->clinic_ids) ? $member->clinic_ids : (json_decode((string)$member->clinic_ids,true) ?: []);
            abort_unless(in_array($clinicId,array_map('intval',(array)$assigned),true),422,'کارمند به شعبه انتخاب‌شده تخصیص ندارد.');
            return;
        }
        $doctorMember = DB::table('clinic_organization_doctors as od')->join('doctors as d','d.id','=','od.doctor_id')
            ->where('od.organization_id',$organizationId)->where('od.status','active')->where('d.user_id',$staffUserId)
            ->select('d.id')->first();
        abort_unless($doctorMember && DB::table('clinic_organization_clinics')->where('organization_id',$organizationId)
            ->where('clinic_id',$clinicId)->where('status','active')->exists()
            && DB::table('addresses')->where('id',$clinicId)->where('doctor_id',$doctorMember->id)->exists(),
            422,'کارمند انتخاب‌شده به شعبه تعلق ندارد.');
    }
}
