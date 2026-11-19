<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Services\Finance\ClinicAccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

final class StaffObligationController extends Controller
{
    use HasActiveDoctor;

    private function ownedOrganization(int $id)
    {
        $user = Auth::guard('web')->user();
        return $this->currentDoctor()->clinicOrganizations()
            ->where('owner_user_id', $user->id)
            ->wherePivot('status', 'active')
            ->whereKey($id)->firstOrFail();
    }

    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();
        abort_unless(in_array($user->role, ['doctor', 'admin'], true), 403);
        $data = $request->validate([
            'organization_id' => ['required','integer'],
            'clinic_id' => ['required','integer'],
            'staff_user_id' => ['required','integer'],
            'kind' => ['required', Rule::in(['advance','loan'])],
            'title' => ['required','string','max:180'],
            'principal_amount' => ['required','integer','min:1','max:999999999999'],
            'installment_count' => ['required','integer','between:1,60'],
            'first_due_date' => ['required','date'],
            'notes' => ['nullable','string','max:2000'],
        ]);
        $organization = $this->ownedOrganization((int)$data['organization_id']);
        $clinicId = (int)$data['clinic_id'];
        abort_unless($organization->clinics()->whereKey($clinicId)->wherePivot('status','active')->exists(), 403);
        $member = $organization->members()->whereKey($data['staff_user_id'])->wherePivot('is_active',true)->firstOrFail();
        $clinicIds = $member->pivot->clinic_ids ?? [];
        if (is_string($clinicIds)) $clinicIds = json_decode($clinicIds, true) ?: [];
        abort_unless(in_array($clinicId, array_map('intval',(array)$clinicIds), true), 403, 'کارمند به این شعبه تخصیص ندارد.');

        $principal = (int)$data['principal_amount'];
        $count = (int)$data['installment_count'];
        $base = intdiv($principal, $count);
        $remainder = $principal % $count;
        $id = DB::table('clinic_staff_obligations')->insertGetId([
            'organization_id'=>$organization->id, 'clinic_id'=>$clinicId,
            'staff_user_id'=>$member->id, 'created_by'=>$user->id,
            'kind'=>$data['kind'], 'title'=>$data['title'],
            'principal_amount'=>$principal, 'installment_amount'=>$base + ($remainder > 0 ? 1 : 0),
            'installment_count'=>$count, 'repaid_amount'=>0, 'outstanding_amount'=>$principal,
            'first_due_date'=>$data['first_due_date'], 'status'=>'draft',
            'notes'=>$data['notes'] ?? null, 'created_at'=>now(), 'updated_at'=>now(),
        ]);
        $firstDue = Carbon::parse($data['first_due_date'])->startOfDay();
        for ($i=1; $i<=$count; $i++) {
            $amount = $base + ($i <= $remainder ? 1 : 0);
            DB::table('clinic_staff_obligation_installments')->insert([
                'obligation_id'=>$id, 'sequence'=>$i, 'due_date'=>$firstDue->copy()->addMonths($i-1)->toDateString(),
                'amount'=>$amount, 'paid_amount'=>0, 'status'=>'pending', 'created_at'=>now(), 'updated_at'=>now(),
            ]);
        }
        return back()->with('success','درخواست مساعده/وام و برنامه اقساط به‌صورت پیش‌نویس ثبت شد.');
    }

    public function approve(int $obligation)
    {
        $user = Auth::guard('web')->user();
        abort_unless(in_array($user->role, ['doctor','admin'], true), 403);
        DB::transaction(function () use ($obligation,$user): void {
            $organizationIds = $this->currentDoctor()->clinicOrganizations()
                ->where('owner_user_id',$user->id)->wherePivot('status','active')
                ->pluck('clinic_organizations.id');
            $row = DB::table('clinic_staff_obligations')->where('id',$obligation)
                ->whereIn('organization_id',$organizationIds)->lockForUpdate()->first();
            abort_unless($row && $row->status === 'draft', 409, 'فقط درخواست پیش‌نویس قابل تأیید است.');
            DB::table('clinic_staff_obligations')->where('id',$row->id)->update([
                'status'=>'approved','approved_by'=>$user->id,'updated_at'=>now(),
            ]);
        },3);
        return back()->with('success','درخواست مساعده/وام تأیید شد؛ برای پرداخت، عملیات پرداخت را انجام دهید.');
    }

    public function disburse(int $obligation, ClinicAccountingService $accounting)
    {
        $user = Auth::guard('web')->user();
        abort_unless(in_array($user->role, ['doctor','admin'], true), 403);
        DB::transaction(function () use ($obligation,$user,$accounting): void {
            $row = DB::table('clinic_staff_obligations as o')
                ->join('clinic_organizations as org','org.id','=','o.organization_id')
                ->where('o.id',$obligation)->where('org.owner_user_id',$user->id)
                ->select('o.*')->lockForUpdate()->first();
            abort_unless($row && $row->status === 'approved', 409, 'فقط درخواست تأییدشده قابل پرداخت است.');
            $code = $row->kind === 'advance' ? '2310' : '2320';
            $accounting->post((int)$row->organization_id,(int)$row->clinic_id,null,(int)$user->id,
                'staff_obligation_disbursement',(int)$row->id,
                $row->kind === 'advance' ? 'پرداخت مساعده کارکنان: '.$row->title : 'پرداخت وام کارکنان: '.$row->title,
                now()->toDateTimeString(),'staff-obligation-disbursement:'.$row->id,
                [['code'=>$code,'debit'=>(int)$row->principal_amount,'credit'=>0],
                 ['code'=>'1000','debit'=>0,'credit'=>(int)$row->principal_amount]]);
            DB::table('clinic_staff_obligations')->where('id',$row->id)->update([
                'status'=>'active','disbursed_by'=>$user->id,'disbursed_at'=>now(),'updated_at'=>now(),
            ]);
        },3);
        return back()->with('success','مساعده/وام پرداخت و به‌عنوان طلب از کارمند در دفتر کل ثبت شد.');
    }

    public function repay(Request $request, int $obligation, ClinicAccountingService $accounting)
    {
        $user = Auth::guard('web')->user();
        abort_unless(in_array($user->role, ['doctor','admin'], true), 403);
        $data = $request->validate([
            'amount'=>['required','integer','min:1','max:999999999999'],
            'method'=>['required',Rule::in(['cash','card','transfer','online','cheque'])],
            'reference'=>['nullable','string','max:120'],
            'paid_at'=>['nullable','date'],
            'notes'=>['nullable','string','max:1000'],
        ]);
        DB::transaction(function () use ($obligation,$user,$data,$accounting): void {
            $row = DB::table('clinic_staff_obligations as o')
                ->join('clinic_organizations as org','org.id','=','o.organization_id')
                ->where('o.id',$obligation)->where('org.owner_user_id',$user->id)
                ->select('o.*')->lockForUpdate()->first();
            abort_unless($row, 404);
            $amount = (int)$data['amount'];
            $key = 'staff-obligation-repayment:'.$row->id.':'.sha1($amount.'|'.($data['reference']??'').'|'.($data['paid_at']??now()->toDateString()));
            if (DB::table('clinic_staff_obligation_repayments')->where('idempotency_key',$key)->exists()) return;
            abort_unless($row->status === 'active', 409, 'این بدهی فعال و قابل وصول نیست.');
            abort_if($amount > (int)$row->outstanding_amount, 422, 'مبلغ وصول از مانده بدهی بیشتر است.');
            $paidAt = Carbon::parse($data['paid_at'] ?? now());
            $repaymentId = DB::table('clinic_staff_obligation_repayments')->insertGetId([
                'obligation_id'=>$row->id,'received_by'=>$user->id,'amount'=>$amount,'method'=>$data['method'],
                'reference'=>$data['reference']??null,'idempotency_key'=>$key,'paid_at'=>$paidAt,
                'notes'=>$data['notes']??null,'created_at'=>now(),'updated_at'=>now(),
            ]);
            $remaining = $amount;
            $installments = DB::table('clinic_staff_obligation_installments')
                ->where('obligation_id',$row->id)->whereColumn('paid_amount','<','amount')
                ->orderBy('sequence')->lockForUpdate()->get();
            foreach ($installments as $installment) {
                if ($remaining < 1) break;
                $due = (int)$installment->amount - (int)$installment->paid_amount;
                $applied = min($remaining,$due);
                $newPaid = (int)$installment->paid_amount + $applied;
                DB::table('clinic_staff_obligation_installments')->where('id',$installment->id)->update([
                    'paid_amount'=>$newPaid,'status'=>$newPaid >= (int)$installment->amount ? 'paid' : 'partial','updated_at'=>now(),
                ]);
                $remaining -= $applied;
            }
            $outstanding = (int)$row->outstanding_amount - $amount;
            $accounting->post((int)$row->organization_id,(int)$row->clinic_id,null,(int)$user->id,
                'staff_obligation_repayment',$repaymentId,'وصول قسط کارکنان: '.$row->title,
                $paidAt->toDateTimeString(),'staff-obligation-repayment:'.$repaymentId,
                [['code'=>'1000','debit'=>$amount,'credit'=>0],
                 ['code'=>$row->kind === 'advance' ? '2310' : '2320','debit'=>0,'credit'=>$amount]]);
            DB::table('clinic_staff_obligations')->where('id',$row->id)->update([
                'repaid_amount'=>(int)$row->repaid_amount+$amount,'outstanding_amount'=>$outstanding,
                'status'=>$outstanding === 0 ? 'settled' : 'active','updated_at'=>now(),
            ]);
        },3);
        return back()->with('success','دریافت وجه ثبت شد و مانده بدهی و اقساط به‌روزرسانی شد.');
    }
}
