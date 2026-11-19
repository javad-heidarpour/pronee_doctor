<?php

declare(strict_types=1);

namespace App\Services\Finance;

use App\Support\Finance\CashFlowForecastCalculator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class ClinicCashFlowForecastService
{
    public function __construct(private readonly CashFlowForecastCalculator $calculator)
    {
    }

    public function forecast(int $doctorId, array $clinicIds, array $organizationIds, int $horizonDays = 90): array
    {
        $horizonDays = max(7, min(180, $horizonDays));
        $start = Carbon::today('Asia/Tehran');
        // The as-of date is day 1 of the requested horizon.
        $end = $start->copy()->addDays($horizonDays - 1);
        $movements = [];
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $movements[$day->toDateString()] = ['inflows' => 0, 'outflows' => 0];
        }

        // Include branch journals plus organization-level journals (clinic_id NULL)
        // exactly once. The previous AND scope discarded organization-level balances.
        $scope = static function ($query, string $clinicColumn, string $organizationColumn) use ($clinicIds, $organizationIds): void {
            if ($clinicIds === [] && $organizationIds === []) {
                $query->whereRaw('1 = 0');
                return;
            }

            $query->where(function ($scope) use ($clinicIds, $organizationIds, $clinicColumn, $organizationColumn): void {
                if ($clinicIds !== []) {
                    $scope->whereIn($clinicColumn, $clinicIds);
                }
                if ($organizationIds !== []) {
                    $method = $clinicIds !== [] ? 'orWhere' : 'where';
                    $scope->{$method}(function ($organizationScope) use ($organizationIds, $organizationColumn, $clinicColumn): void {
                        $organizationScope->whereIn($organizationColumn, $organizationIds)
                            ->whereNull($clinicColumn);
                    });
                }
            });
        };

        $cashQuery = DB::table('clinic_journal_lines as lines')
            ->join('clinic_financial_accounts as accounts', 'accounts.id', '=', 'lines.account_id')
            ->join('clinic_journal_entries as entries', 'entries.id', '=', 'lines.journal_entry_id')
            ->where('entries.status', 'posted')->where('accounts.code', '1000');
        $scope($cashQuery, 'entries.clinic_id', 'entries.organization_id');
        $startingBalance = (int) $cashQuery->selectRaw('COALESCE(SUM(lines.debit - lines.credit), 0) as balance')->value('balance');

        $installments = DB::table('patient_treatment_installments as installments')
            ->join('patient_treatment_plans as plans', 'plans.id', '=', 'installments.treatment_plan_id')
            ->where('plans.status', 'active')
            ->whereIn('installments.status', ['pending', 'partial', 'overdue'])
            ->whereColumn('installments.amount', '>', 'installments.paid_amount')
            ->whereDate('installments.due_date', '<=', $end->toDateString());
        if ($organizationIds !== []) {
            $installments->whereIn('plans.organization_id', $organizationIds);
        } else {
            $installments->where('plans.doctor_id', $doctorId);
        }
        $installments->whereIn('plans.clinic_id', $clinicIds);
        foreach ($installments->get(['installments.due_date', 'installments.amount', 'installments.paid_amount']) as $row) {
            $date = max($start->toDateString(), Carbon::parse($row->due_date)->toDateString());
            $movements[$date]['inflows'] += max(0, (int) $row->amount - (int) $row->paid_amount);
        }

        $payrolls = DB::table('clinic_staff_payrolls')
            ->whereNull('paid_at')->where('status', 'approved')
            ->whereDate('period_end', '<=', $end->toDateString());
        if ($organizationIds !== []) {
            $payrolls->whereIn('organization_id', $organizationIds);
        } else {
            $payrolls->where('organization_id', null);
        }
        if ($clinicIds === [] && $organizationIds === []) {
            $payrolls->whereRaw('1 = 0');
        } else {
            $payrolls->where(function ($query) use ($clinicIds, $organizationIds): void {
            if ($clinicIds !== []) $query->whereIn('clinic_id', $clinicIds);
            if ($organizationIds !== []) {
                $method = $clinicIds !== [] ? 'orWhere' : 'where';
                $query->{$method}(function ($orgScope) use ($organizationIds): void {
                    $orgScope->whereIn('organization_id', $organizationIds)->whereNull('clinic_id');
                });
            }
            });
        }
        foreach ($payrolls->get(['period_end', 'net_amount']) as $row) {
            $date = max($start->toDateString(), Carbon::parse($row->period_end)->toDateString());
            $movements[$date]['outflows'] += (int) $row->net_amount;
        }

        $approvedDisbursements = DB::table('clinic_staff_obligations')
            ->where('status', 'approved')->whereNull('disbursed_at')
            ->where(function ($query) use ($end): void {
                $query->whereDate('planned_disbursement_date', '<=', $end->toDateString())->orWhereNull('planned_disbursement_date');
            });
        if ($organizationIds !== []) {
            $approvedDisbursements->whereIn('organization_id', $organizationIds);
        }
        $approvedDisbursements->whereIn('clinic_id', $clinicIds);
        // The schema has no planned-disbursement date. Approved, unpaid
        // obligations are assumed paid on the forecast date. first_due_date
        // is a repayment date and is not a valid cash-out date.
        foreach ($approvedDisbursements->get(['principal_amount', 'planned_disbursement_date', 'created_at']) as $row) {
            $plannedDate = $row->planned_disbursement_date ?: Carbon::parse($row->created_at)->toDateString();
            $date = max($start->toDateString(), Carbon::parse($plannedDate)->toDateString());
            $movements[$date]['outflows'] += (int) $row->principal_amount;
        }

        $repayments = DB::table('clinic_staff_obligation_installments as installments')
            ->join('clinic_staff_obligations as obligations', 'obligations.id', '=', 'installments.obligation_id')
            ->where('obligations.status', 'active')
            ->whereColumn('installments.amount', '>', 'installments.paid_amount')
            ->whereDate('installments.due_date', '<=', $end->toDateString());
        if ($organizationIds !== []) {
            $repayments->whereIn('obligations.organization_id', $organizationIds);
        }
        $repayments->whereIn('obligations.clinic_id', $clinicIds);
        foreach ($repayments->get(['installments.due_date', 'installments.amount', 'installments.paid_amount']) as $row) {
            $date = max($start->toDateString(), Carbon::parse($row->due_date)->toDateString());
            $movements[$date]['inflows'] += max(0, (int) $row->amount - (int) $row->paid_amount);
        }

        // No contractual due date exists yet; project outstanding received purchases as due today.
        $paidByOrder = DB::table('clinic_supplier_payments')->select('purchase_order_id')->selectRaw('SUM(amount_rial) as paid_total')->groupBy('purchase_order_id');
        $supplierPayables = DB::table('clinic_purchase_orders as orders')
            ->join('clinic_purchase_order_items as items', 'items.purchase_order_id', '=', 'orders.id')
            ->leftJoinSub($paidByOrder, 'payments', 'payments.purchase_order_id', '=', 'orders.id')
            ->whereIn('orders.status', ['partially_received', 'received'])->whereIn('orders.clinic_id', $clinicIds)
            ->groupBy('orders.id', 'payments.paid_total')
            ->selectRaw('orders.id, SUM(ROUND(items.received_quantity * items.unit_cost_rial)) - COALESCE(payments.paid_total, 0) as outstanding');
        foreach ($supplierPayables->get() as $payable) $movements[$start->toDateString()]['outflows'] += max(0, (int) $payable->outstanding);

        $scenarios = [
            'conservative' => $this->calculator->project($startingBalance, $movements, $horizonDays, 0.65),
            'realistic' => $this->calculator->project($startingBalance, $movements, $horizonDays, 0.85),
            'optimistic' => $this->calculator->project($startingBalance, $movements, $horizonDays, 1.0),
        ];

        return [
            'horizon_days' => $horizonDays,
            'as_of' => $start->toDateString(),
            'starting_balance' => $startingBalance,
            'scenarios' => $scenarios,
            'assumptions' => [
                'collection_factors' => ['conservative' => 0.65, 'realistic' => 0.85, 'optimistic' => 1.0],
                'included' => ['posted_cash_account_1000_in_accessible_branches_and_organization_level', 'unpaid_installments_on_active_treatment_plans', 'approved_unpaid_payroll', 'approved_undisbursed_staff_obligations_on_planned_disbursement_date', 'active_staff_loan_repayments', 'received_unpaid_supplier_invoices_assumed_due_as_of_forecast_date'],
                'excluded' => ['unrecorded_recurring_expenses', 'unissued_checks', 'bank_feed_transactions', 'unapproved_payroll', 'unconfirmed_future_revenue', 'inactive_or_cancelled_treatment_plans'],
                'currency' => 'IRR',
            ],
        ];
    }
}
