<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use App\Models\ClinicInventoryItem;
use App\Services\Finance\ClinicAccountingService as FinanceAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ProcurementController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $doctor = $this->currentDoctor();
        $user = Auth::guard('web')->user();
        if ($user?->role === 'secretary') {
            abort_unless($this->secretaryHas($doctor, 'inventory.view') || $this->secretaryHas($doctor, 'inventory.manage'), 403);
        }
        $clinicIds = $this->accessibleClinicIds($doctor);
        $suppliers = DB::table('clinic_suppliers')->where('doctor_id', $doctor->id)
            ->where('is_active', true)->orderBy('name')->get();
        $clinics = Address::query()->whereIn('id', $clinicIds)->where('is_active', true)->get(['id','name']);
        $inventory = ClinicInventoryItem::query()->whereIn('clinic_id', $clinicIds)->where('is_active', true)
            ->orderBy('name')->get(['id','clinic_id','name','sku','unit','current_quantity','minimum_quantity','average_unit_cost']);
        $quotes = DB::table('clinic_supplier_quotes as q')
            ->join('clinic_suppliers as s','s.id','=','q.supplier_id')
            ->join('clinic_inventory_items as i','i.id','=','q.inventory_item_id')
            ->where('q.doctor_id',$doctor->id)->whereIn('q.clinic_id',$clinicIds)
            ->where('q.is_active',true)->where('s.is_active',true)
            ->where(fn($b)=>$b->whereNull('q.valid_until')->orWhereDate('q.valid_until','>=',today()))
            ->orderBy('q.unit_cost_rial')->get(['q.id','q.clinic_id','q.supplier_id','q.inventory_item_id','q.minimum_quantity','q.unit_cost_rial','q.lead_time_days','q.valid_until','s.name as supplier_name','i.name as item_name','i.unit']);
        $orders = DB::table('clinic_purchase_orders as o')->join('clinic_suppliers as s','s.id','=','o.supplier_id')
            ->where('o.doctor_id',$doctor->id)->whereIn('o.clinic_id',$clinicIds)->orderByDesc('o.id')->limit(40)->get([
                'o.id','o.order_number','o.clinic_id','o.status','o.total_amount_rial','o.created_at','s.name as supplier_name',
                DB::raw('(SELECT COALESCE(SUM(ROUND(oi.received_quantity * oi.unit_cost_rial)),0) FROM clinic_purchase_order_items oi WHERE oi.purchase_order_id=o.id) as received_total_rial'),
                DB::raw('(SELECT COALESCE(SUM(sp.amount_rial),0) FROM clinic_supplier_payments sp WHERE sp.purchase_order_id=o.id) as paid_total_rial'),
                DB::raw('CASE WHEN (SELECT COALESCE(SUM(ROUND(oi.received_quantity * oi.unit_cost_rial)),0) FROM clinic_purchase_order_items oi WHERE oi.purchase_order_id=o.id) > (SELECT COALESCE(SUM(sp.amount_rial),0) FROM clinic_supplier_payments sp WHERE sp.purchase_order_id=o.id) THEN (SELECT COALESCE(SUM(ROUND(oi.received_quantity * oi.unit_cost_rial)),0) FROM clinic_purchase_order_items oi WHERE oi.purchase_order_id=o.id) - (SELECT COALESCE(SUM(sp.amount_rial),0) FROM clinic_supplier_payments sp WHERE sp.purchase_order_id=o.id) ELSE 0 END as outstanding_rial'),
            ]);
        $orderItems = DB::table('clinic_purchase_order_items as oi')->join('clinic_inventory_items as i','i.id','=','oi.inventory_item_id')
            ->join('clinic_purchase_orders as o','o.id','=','oi.purchase_order_id')
            ->where('o.doctor_id',$doctor->id)->whereIn('o.clinic_id',$clinicIds)->orderBy('oi.id')
            ->get(['oi.id','oi.purchase_order_id','oi.ordered_quantity','oi.received_quantity','oi.unit_cost_rial','i.name as item_name','i.unit']);
        return Inertia::render('Portal/Procurement/Index', [
            'doctor'=>$doctor,'clinics'=>$clinics,'suppliers'=>$suppliers,'inventory'=>$inventory,
            'quotes'=>$quotes,'orders'=>$orders,'orderItems'=>$orderItems,
            'canManage'=>Auth::guard('web')->user()?->role !== 'secretary' || $this->secretaryHas($doctor,'inventory.manage'),
            'canApprove'=>Auth::guard('web')->user()?->role !== 'secretary',
        ]);
    }

    public function storeSupplier(Request $request)
    {
        $doctor=$this->currentDoctor();$this->authorizeSecretaryPermission('inventory.manage',$doctor);
        $data=$request->validate([
            'clinic_id'=>['nullable','integer'],'name'=>['required','string','max:180'],
            'contact_name'=>['nullable','string','max:120'],'phone'=>['nullable','string','max:40'],
            'email'=>['nullable','email','max:180'],'tax_id'=>['nullable','string','max:80'],
            'address'=>['nullable','string','max:500'],'notes'=>['nullable','string','max:3000'],
        ]);
        if(!empty($data['clinic_id']))$this->authorizeClinicAccess((int)$data['clinic_id'],$doctor);
        DB::table('clinic_suppliers')->insert($data+['doctor_id'=>$doctor->id,'is_active'=>true,'created_at'=>now(),'updated_at'=>now()]);
        return back()->with('success','تأمین‌کننده ثبت شد.');
    }

    public function storeQuote(Request $request)
    {
        $doctor=$this->currentDoctor();$this->authorizeSecretaryPermission('inventory.manage',$doctor);
        $data=$request->validate([
            'clinic_id'=>['required','integer'],'supplier_id'=>['required','integer'],
            'inventory_item_id'=>['required','integer'],'minimum_quantity'=>['required','numeric','gt:0','decimal:0,3','max:999999'],
            'unit_cost_rial'=>['required','integer','min:0','max:999999999999'],
            'lead_time_days'=>['nullable','integer','min:0','max:365'],'valid_until'=>['nullable','date','after_or_equal:today'],
            'notes'=>['nullable','string','max:2000'],
        ]);
        $clinicId=(int)$data['clinic_id'];$this->authorizeClinicAccess($clinicId,$doctor);
        $supplier=DB::table('clinic_suppliers')->where('doctor_id',$doctor->id)->where('is_active',true)->where(fn($q)=>$q->whereNull('clinic_id')->orWhere('clinic_id',$clinicId))->where('id',$data['supplier_id'])->first();
        abort_unless($supplier,404);
        $item=ClinicInventoryItem::query()->whereKey($data['inventory_item_id'])->where('clinic_id',$clinicId)->firstOrFail();
        DB::table('clinic_supplier_quotes')->insert([
            'doctor_id'=>$doctor->id,'clinic_id'=>$clinicId,'supplier_id'=>$supplier->id,'inventory_item_id'=>$item->id,
            'minimum_quantity'=>$data['minimum_quantity'],'unit_cost_rial'=>$data['unit_cost_rial'],
            'lead_time_days'=>$data['lead_time_days']??0,'valid_until'=>$data['valid_until']??null,
            'notes'=>$data['notes']??null,'is_active'=>true,'created_at'=>now(),'updated_at'=>now(),
        ]);
        return back()->with('success','پیشنهاد قیمت ثبت شد و در مقایسه تأمین‌کنندگان قرار گرفت.');
    }

    public function createOrder(Request $request,int $quote)
    {
        $doctor=$this->currentDoctor();$this->authorizeSecretaryPermission('inventory.manage',$doctor);
        $data=$request->validate(['quantity'=>['required','numeric','gt:0','decimal:0,3','max:999999']]);
        $clinicIds=$this->accessibleClinicIds($doctor);
        DB::transaction(function()use($doctor,$quote,$data,$clinicIds):void{
            $offer=DB::table('clinic_supplier_quotes')->where('id',$quote)->where('doctor_id',$doctor->id)
                ->whereIn('clinic_id',$clinicIds)->where('is_active',true)->lockForUpdate()->first();
            abort_unless($offer,404);
            abort_if($offer->valid_until && $offer->valid_until<today()->toDateString(),422,'اعتبار پیشنهاد قیمت پایان یافته است.');
            abort_if((float)$data['quantity']<(float)$offer->minimum_quantity,422,'مقدار سفارش از حداقل مقدار تأمین‌کننده کمتر است.');
            $item=ClinicInventoryItem::query()->whereKey($offer->inventory_item_id)->where('clinic_id',$offer->clinic_id)->firstOrFail();
            $total=(int)round((float)$data['quantity']*(int)$offer->unit_cost_rial);
            $orderId=DB::table('clinic_purchase_orders')->insertGetId([
                'doctor_id'=>$doctor->id,'clinic_id'=>$offer->clinic_id,'supplier_id'=>$offer->supplier_id,'quote_id'=>$offer->id,
                'order_number'=>'PX-PO-'.now()->format('ymdHis').'-'.Str::upper(Str::random(6)),
                'status'=>'draft','total_amount_rial'=>$total,'created_by'=>Auth::guard('web')->id(),
                'created_at'=>now(),'updated_at'=>now(),
            ]);
            DB::table('clinic_purchase_order_items')->insert([
                'purchase_order_id'=>$orderId,'inventory_item_id'=>$item->id,'ordered_quantity'=>$data['quantity'],
                'received_quantity'=>0,'unit_cost_rial'=>$offer->unit_cost_rial,'line_total_rial'=>$total,
                'created_at'=>now(),'updated_at'=>now(),
            ]);
        },3);
        return back()->with('success','پیش‌نویس سفارش خرید ایجاد شد؛ برای اجرا باید تأیید شود.');
    }

    public function approveOrder(int $order)
    {
        $this->authorizeDoctorOnly();$doctor=$this->currentDoctor();
        DB::transaction(function()use($doctor,$order):void{
            $row=DB::table('clinic_purchase_orders')->where('id',$order)->where('doctor_id',$doctor->id)
                ->whereIn('clinic_id',$this->accessibleClinicIds($doctor))->lockForUpdate()->first();
            abort_unless($row,404);abort_unless($row->status==='draft',409,'فقط سفارش پیش‌نویس قابل تأیید است.');
            DB::table('clinic_purchase_orders')->where('id',$order)->update([
                'status'=>'approved','approved_by'=>Auth::guard('web')->id(),'approved_at'=>now(),'updated_at'=>now(),
            ]);
        },3);
        return back()->with('success','سفارش خرید تأیید شد.');
    }

    public function receiveItem(Request $request,int $order,int $item,FinanceAccountingService $accounting)
    {
        $doctor=$this->currentDoctor();$this->authorizeSecretaryPermission('inventory.manage',$doctor);
        $data=$request->validate([
            'quantity'=>['required','numeric','gt:0','decimal:0,3','max:999999'],
            // کلید هر تلاش دریافت در مرورگر ساخته و در ارسال مجدد همان درخواست تکرار می‌شود.
            'idempotency_key'=>['required','uuid'],
        ]);
        DB::transaction(function()use($doctor,$order,$item,$data,$accounting):void{
            $purchase=DB::table('clinic_purchase_orders')->where('id',$order)->where('doctor_id',$doctor->id)
                ->whereIn('clinic_id',$this->accessibleClinicIds($doctor))->lockForUpdate()->first();
            abort_unless($purchase,404);
            $line=DB::table('clinic_purchase_order_items')->where('id',$item)->where('purchase_order_id',$order)->lockForUpdate()->first();
            abort_unless($line,404);

            // اگر درخواست قبلاً ثبت شده باشد، همان نتیجه قبلی برگردانده می‌شود و موجودی دوباره تغییر نمی‌کند.
            $existing=DB::table('clinic_inventory_movements')->where('idempotency_key',$data['idempotency_key'])->first();
            if($existing){
                abort_unless((int)$existing->inventory_item_id===(int)$line->inventory_item_id
                    && $existing->movement_type==='purchase'
                    && round((float)$existing->quantity,3)===round((float)$data['quantity'],3)
                    && $existing->reference===$purchase->order_number
                    && $existing->notes==='دریافت سفارش خرید '.$purchase->order_number.' - ردیف '.$line->id,409,
                    'کلید تکرار درخواست قبلاً برای دریافت دیگری استفاده شده است.');
                return;
            }

            abort_unless(in_array($purchase->status,['approved','partially_received'],true),409,'فقط سفارش تأییدشده قابل دریافت است.');
            $remaining=round((float)$line->ordered_quantity-(float)$line->received_quantity,3);
            $quantity=round((float)$data['quantity'],3);abort_if($quantity>$remaining,422,'مقدار دریافتی از باقیمانده سفارش بیشتر است.');
            $stock=ClinicInventoryItem::query()->whereKey($line->inventory_item_id)->where('clinic_id',$purchase->clinic_id)->lockForUpdate()->firstOrFail();
            $old=(float)$stock->current_quantity;$new=$old+$quantity;
            $stock->average_unit_cost=(int)round((($old*(int)$stock->average_unit_cost)+($quantity*(int)$line->unit_cost_rial))/$new);
            $stock->current_quantity=$new;$stock->save();
            $movement=$stock->movements()->create([
                'recorded_by'=>Auth::guard('web')->id(),'movement_type'=>'purchase','quantity'=>$quantity,
                'unit_cost'=>$line->unit_cost_rial,'reference'=>$purchase->order_number,
                'idempotency_key'=>$data['idempotency_key'],
                'notes'=>'دریافت سفارش خرید '.$purchase->order_number.' - ردیف '.$line->id,'occurred_at'=>now(),
            ]);
            $accounting->recordInventoryMovement($stock->organization_id,$stock->clinic_id,$doctor->id,
                (int)Auth::guard('web')->id(),$movement->id,'purchase_credit',(int)round($quantity*(int)$line->unit_cost_rial),
                'دریافت خرید: '.$stock->name,now()->toDateTimeString());
            $received=round((float)$line->received_quantity+$quantity,3);
            DB::table('clinic_purchase_order_items')->where('id',$line->id)->update(['received_quantity'=>$received,'updated_at'=>now()]);
            $remainingLines=DB::table('clinic_purchase_order_items')->where('purchase_order_id',$order)->get();
            $complete=$remainingLines->every(fn($r)=>(float)$r->received_quantity >= (float)$r->ordered_quantity);
            $any=$remainingLines->contains(fn($r)=>(float)$r->received_quantity>0);
            DB::table('clinic_purchase_orders')->where('id',$order)->update([
                'status'=>$complete?'received':($any?'partially_received':'approved'),
                'received_at'=>$complete?now():null,'updated_at'=>now(),
            ]);
        },3);
        return back()->with('success','دریافت کالا ثبت و موجودی انبار و حسابداری به‌روزرسانی شد.');
    }
    public function paySupplier(Request $request, int $order, FinanceAccountingService $accounting)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('inventory.manage', $doctor);
        $data = $request->validate([
            'amount_rial' => ['required','integer','min:1','max:999999999999'],
            'method' => ['required', Rule::in(['cash','card','transfer','cheque'])],
            'reference' => ['required','string','max:120'], 'paid_at' => ['nullable','date'],
        ]);

        DB::transaction(function () use ($doctor, $order, $data, $accounting): void {
            $purchase = DB::table('clinic_purchase_orders')->where('id',$order)->where('doctor_id',$doctor->id)
                ->whereIn('clinic_id',$this->accessibleClinicIds($doctor))->lockForUpdate()->first();
            abort_unless($purchase,404);
            $amount = (int) $data['amount_rial'];
            // ابتدا retry دقیق را تشخیص می‌دهیم؛ اعتبارسنجی unique در ابتدای درخواست، retry سالم را بی‌دلیل رد می‌کرد.
            $key = 'supplier-payment:' . $order . ':' . hash('sha256',$amount . '|' . $data['reference']);
            $existing = DB::table('clinic_supplier_payments')->where('idempotency_key',$key)->first();
            if ($existing) {
                abort_unless(
                    (int) $existing->purchase_order_id === $order
                    && (int) $existing->amount_rial === $amount
                    && $existing->reference === $data['reference']
                    && $existing->method === $data['method'],
                    409,
                    'کلید تکرار پرداخت با پرداخت قبلی تطبیق ندارد.'
                );
                return;
            }

            // قید یکتای پایگاه داده نیز درخواست‌های هم‌زمان را پوشش می‌دهد؛ این بررسی پیام خطای قابل‌فهم می‌دهد.
            $sameReference = DB::table('clinic_supplier_payments')
                ->where('doctor_id',$doctor->id)->where('reference',$data['reference'])->first();
            abort_if($sameReference,409,'این شماره پیگیری قبلاً برای پرداخت دیگری ثبت شده است.');

            abort_unless(in_array($purchase->status,['partially_received','received'],true),409,'ابتدا باید کالا دریافت شده باشد.');
            $received = (int) DB::table('clinic_purchase_order_items')->where('purchase_order_id',$order)
                ->selectRaw('COALESCE(SUM(ROUND(received_quantity * unit_cost_rial)),0) as total')->value('total');
            $paid = (int) DB::table('clinic_supplier_payments')->where('purchase_order_id',$order)->sum('amount_rial');
            abort_if($amount > max(0,$received-$paid),422,'مبلغ پرداخت از بدهی باقیمانده بیشتر است.');
            $paymentId = DB::table('clinic_supplier_payments')->insertGetId([
                'purchase_order_id'=>$order,'supplier_id'=>$purchase->supplier_id,'doctor_id'=>$doctor->id,'clinic_id'=>$purchase->clinic_id,
                'amount_rial'=>$amount,'method'=>$data['method'],'reference'=>$data['reference'],'recorded_by'=>Auth::guard('web')->id(),
                'paid_at'=>$data['paid_at']??now(),'idempotency_key'=>$key,'created_at'=>now(),'updated_at'=>now(),
            ]);
            $organizationId = Address::query()->whereKey($purchase->clinic_id)->value('organization_id');
            $accounting->post($organizationId ? (int)$organizationId : null,(int)$purchase->clinic_id,(int)$doctor->id,
                Auth::guard('web')->id(),'supplier_payment',$paymentId,'پرداخت به تأمین‌کننده سفارش '.$purchase->order_number,
                Carbon::parse($data['paid_at']??now())->toDateTimeString(),'supplier-payment-journal:'.$paymentId,
                [['code'=>'2400','debit'=>$amount,'credit'=>0],['code'=>'1000','debit'=>0,'credit'=>$amount]]);
        },3);

        return back()->with('success','پرداخت تأمین‌کننده ثبت و مانده بدهی در دفتر کل تسویه شد.');
    }

    private function secretaryHas($doctor, string $permission): bool
    {
        $user = Auth::guard('web')->user();
        $assignment = $user?->doctors()->where('doctors.id', $doctor->id)->first();
        $permissions = $assignment?->pivot?->permissions ?? [];
        if (is_string($permissions)) {
            $permissions = json_decode($permissions, true) ?: [];
        }

        return is_array($permissions) && in_array($permission, $permissions, true);
    }

}
