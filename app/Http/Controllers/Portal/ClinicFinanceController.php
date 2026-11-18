<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\ClinicExpense;
use App\Models\Patient;
use App\Models\PatientCollateral;
use App\Models\PatientInstallmentPayment;
use App\Models\PatientTreatmentInstallment;
use App\Models\PatientTreatmentPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

class ClinicFinanceController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);

        $plans = PatientTreatmentPlan::query()
            ->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds)
            ->with(['patient:id,name,phone', 'clinic:id,name', 'installments', 'collaterals'])
            ->latest()->paginate(15)->withQueryString();

        $installments = PatientTreatmentInstallment::query()
            ->whereHas('plan', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds))
            ->with(['plan.patient:id,name,phone', 'plan.clinic:id,name'])
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')->limit(100)->get();

        $expenses = ClinicExpense::query()->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $clinicIds)->with('clinic:id,name')
            ->latest('expense_date')->limit(20)->get();

        $ledger = DB::table('clinic_financial_ledger')
            ->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds);
        $income = (clone $ledger)->sum('credit');
        $outgoing = (clone $ledger)->sum('debit');

        return Inertia::render('Portal/Finance/Index', [
            'doctor' => $doctor,
            'clinics' => $doctor->address()->whereIn('id', $clinicIds)->get(['id', 'name']),
            'patients' => Patient::query()->where('doctor_id', $doctor->id)
                ->whereHas('appointments', fn ($q) => $q->whereIn('clinic_id', $clinicIds))
                ->orderBy('name')->get(['id', 'name', 'phone']),
            'plans' => $plans,
            'installments' => $installments,
            'expenses' => $expenses,
            'stats' => [
                'plans' => PatientTreatmentPlan::where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds)->count(),
                'receivables' => PatientTreatmentInstallment::whereHas('plan', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds))->selectRaw('COALESCE(SUM(amount - paid_amount),0) as total')->value('total'),
                'overdue' => PatientTreatmentInstallment::whereHas('plan', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $clinicIds))->whereDate('due_date', '<', today())->whereColumn('paid_amount', '<', 'amount')->count(),
                'income' => (int) $income,
                'expenses' => (int) $outgoing,
            ],
        ]);
    }

    public function storePlan(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer'],
            'patient_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:180'],
            'specialty_module' => ['required', Rule::in(['general', 'dentistry', 'dermatology', 'physiotherapy', 'surgery', 'other'])],
            'total_amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'discount_amount' => ['nullable', 'integer', 'min:0'],
            'deposit_amount' => ['nullable', 'integer', 'min:0'],
            'installment_count' => ['required', 'integer', 'min:1', 'max:48'],
            'first_due_date' => ['required', 'date', 'after_or_equal:today'],
            'clinical_notes' => ['nullable', 'string', 'max:5000'],
            'financial_terms' => ['nullable', 'string', 'max:3000'],
        ]);

        $clinicId = (int) $data['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $patient = Patient::query()->whereKey($data['patient_id'])->where('doctor_id', $doctor->id)
            ->whereHas('appointments', fn ($q) => $q->where('clinic_id', $clinicId))->firstOrFail();

        $total = (int) $data['total_amount'];
        $discount = (int) ($data['discount_amount'] ?? 0);
        $deposit = (int) ($data['deposit_amount'] ?? 0);
        $net = $total - $discount;
        abort_if($discount > $total || $deposit > $net, 422, 'مبلغ تخفیف یا پیش‌پرداخت از مبلغ قابل پرداخت بیشتر است.');

        DB::transaction(function () use ($doctor, $patient, $clinicId, $data, $total, $discount, $deposit, $net): void {
            $plan = PatientTreatmentPlan::create([
                'doctor_id' => $doctor->id, 'clinic_id' => $clinicId, 'patient_id' => $patient->id,
                'created_by' => Auth::guard('web')->id(), 'title' => $data['title'],
                'specialty_module' => $data['specialty_module'], 'status' => 'active',
                'total_amount' => $total, 'discount_amount' => $discount, 'deposit_amount' => $deposit,
                'installment_count' => $data['installment_count'],
                'clinical_notes' => $data['clinical_notes'] ?? null,
                'financial_terms' => $data['financial_terms'] ?? null, 'started_at' => today(),
            ]);

            $remaining = $net - $deposit;
            $count = (int) $data['installment_count'];
            $base = intdiv($remaining, $count);
            $remainder = $remaining % $count;
            $firstDue = Carbon::parse($data['first_due_date']);

            if ($deposit > 0) {
                $depositRow = $plan->installments()->create([
                    'sequence' => 0, 'amount' => $deposit, 'paid_amount' => $deposit,
                    'due_date' => today(), 'status' => 'paid',
                ]);
                $payment = PatientInstallmentPayment::create([
                    'installment_id' => $depositRow->id,
                    'received_by' => Auth::guard('web')->id(), 'amount' => $deposit,
                    'method' => 'cash', 'reference' => 'DEPOSIT-' . $plan->id,
                    'paid_at' => now(), 'notes' => 'پیش‌پرداخت طرح درمان',
                ]);
                DB::table('clinic_financial_ledger')->insert([
                    'doctor_id' => $doctor->id, 'clinic_id' => $clinicId, 'patient_id' => $patient->id,
                    'treatment_plan_id' => $plan->id, 'installment_payment_id' => $payment->id,
                    'recorded_by' => Auth::guard('web')->id(), 'entry_type' => 'patient_payment',
                    'description' => 'پیش‌پرداخت طرح درمان: ' . $plan->title,
                    'debit' => 0, 'credit' => $deposit, 'occurred_at' => now(),
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            for ($i = 1; $i <= $count && $remaining > 0; $i++) {
                $amount = $base + ($i <= $remainder ? 1 : 0);
                if ($amount <= 0) continue;
                $plan->installments()->create([
                    'sequence' => $i, 'amount' => $amount, 'due_date' => $firstDue->copy()->addMonths($i - 1),
                    'status' => 'pending',
                ]);
            }
        });

        return back()->with('success', 'طرح درمان و برنامه اقساط ثبت شد.');
    }

    public function recordPayment(Request $request, int $installment)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'method' => ['required', Rule::in(['cash', 'card', 'transfer', 'online', 'cheque'])],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        DB::transaction(function () use ($doctor, $installment, $data): void {
            $row = PatientTreatmentInstallment::query()->whereKey($installment)
                ->whereHas('plan', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $this->accessibleClinicIds($doctor)))
                ->lockForUpdate()->firstOrFail();
            $remaining = (int) $row->amount - (int) $row->paid_amount;
            abort_if((int) $data['amount'] > $remaining, 422, 'مبلغ دریافتی از مانده قسط بیشتر است.');

            $payment = $row->payments()->create([
                'received_by' => Auth::guard('web')->id(), 'amount' => $data['amount'],
                'method' => $data['method'], 'reference' => $data['reference'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(), 'notes' => $data['notes'] ?? null,
            ]);
            $newPaid = (int) $row->paid_amount + (int) $data['amount'];
            $row->update(['paid_amount' => $newPaid, 'status' => $newPaid >= $row->amount ? 'paid' : 'partial']);

            $plan = $row->plan;
            DB::table('clinic_financial_ledger')->insert([
                'doctor_id' => $plan->doctor_id, 'clinic_id' => $plan->clinic_id,
                'patient_id' => $plan->patient_id, 'treatment_plan_id' => $plan->id,
                'installment_payment_id' => $payment->id, 'recorded_by' => Auth::guard('web')->id(),
                'entry_type' => 'patient_payment', 'description' => 'دریافت قسط طرح درمان: ' . $plan->title,
                'debit' => 0, 'credit' => $data['amount'], 'occurred_at' => $payment->paid_at,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });

        return back()->with('success', 'دریافت قسط ثبت شد.');
    }

    public function storeCollateral(Request $request, int $plan)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate([
            'type' => ['required', Rule::in(['cheque', 'promissory_note', 'guarantor', 'other'])],
            'identifier' => ['nullable', 'string', 'max:120'],
            'face_value' => ['nullable', 'integer', 'min:1'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $treatment = PatientTreatmentPlan::query()->whereKey($plan)->where('doctor_id', $doctor->id)
            ->whereIn('clinic_id', $this->accessibleClinicIds($doctor))->firstOrFail();
        $treatment->collaterals()->create($data + ['received_by' => Auth::guard('web')->id(), 'status' => 'held', 'received_at' => now()]);
        return back()->with('success', 'وثیقه ثبت شد. این ثبت به‌عنوان وجه دریافتی محسوب نمی‌شود.');
    }

    public function updateCollateral(Request $request, int $collateral)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate(['status' => ['required', Rule::in(['held', 'returned', 'collected', 'cancelled'])], 'notes' => ['nullable', 'string', 'max:2000']]);
        $item = PatientCollateral::query()->whereKey($collateral)->whereHas('plan', fn ($q) => $q->where('doctor_id', $doctor->id)->whereIn('clinic_id', $this->accessibleClinicIds($doctor)))->firstOrFail();
        abort_if($item->status === 'returned' || $item->status === 'collected', 422, 'وضعیت نهایی وثیقه قابل تغییر نیست.');
        $item->update($data + ['returned_at' => $data['status'] === 'returned' ? now() : null]);
        return back()->with('success', 'وضعیت وثیقه به‌روزرسانی شد.');
    }

    public function storeExpense(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer'], 'category' => ['required', 'string', 'max:60'],
            'description' => ['required', 'string', 'max:180'], 'amount' => ['required', 'integer', 'min:1', 'max:999999999999'],
            'expense_date' => ['required', 'date'], 'payment_method' => ['required', Rule::in(['cash', 'card', 'transfer', 'cheque'])],
            'reference' => ['nullable', 'string', 'max:120'], 'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        DB::transaction(function () use ($doctor, $clinicId, $data): void {
            $expense = ClinicExpense::create($data + ['doctor_id' => $doctor->id, 'recorded_by' => Auth::guard('web')->id()]);
            DB::table('clinic_financial_ledger')->insert([
                'doctor_id' => $doctor->id, 'clinic_id' => $clinicId, 'expense_id' => $expense->id,
                'recorded_by' => Auth::guard('web')->id(), 'entry_type' => 'clinic_expense',
                'description' => $data['description'], 'debit' => $data['amount'], 'credit' => 0,
                'occurred_at' => Carbon::parse($data['expense_date'])->startOfDay(),
                'created_at' => now(), 'updated_at' => now(),
            ]);
        });
        return back()->with('success', 'هزینه شعبه ثبت شد.');
    }
}
