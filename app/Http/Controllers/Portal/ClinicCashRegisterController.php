<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\ClinicCashRegisterSession;
use App\Models\ClinicCashTransaction;
use App\Models\Address;
use App\Services\Finance\ClinicAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class ClinicCashRegisterController extends Controller
{
    use HasActiveDoctor;

    public function index(Request $request)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.view', $doctor);
        $clinicIds = $this->accessibleClinicIds($doctor);
        abort_if($clinicIds === [], 403, 'شعبه مجازی برای صندوق در دسترس نیست.');

        $data = $request->validate(['clinic_id' => ['nullable', 'integer']]);
        $clinicId = isset($data['clinic_id']) ? (int) $data['clinic_id'] : (int) $clinicIds[0];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $clinic = Address::query()->whereKey($clinicId)->where('doctor_id', $doctor->id)->firstOrFail();

        $session = ClinicCashRegisterSession::query()
            ->where('doctor_id', $doctor->id)->where('clinic_id', $clinicId)->where('status', 'open')
            ->with(['transactions' => fn ($query) => $query->latest('id')])
            ->first();

        $balance = null;
        if ($session) {
            $in = (int) $session->transactions->where('direction', 'in')->sum('amount_rial');
            $out = (int) $session->transactions->where('direction', 'out')->sum('amount_rial');
            $balance = (int) $session->opening_balance_rial + $in - $out;
        }

        return Inertia::render('Portal/Finance/CashRegister', [
            'doctor' => $doctor,
            'clinics' => Address::query()->where('doctor_id', $doctor->id)->whereIn('id', $clinicIds)->where('is_active', true)->orderBy('name')->get(['id','name']),
            'clinic' => ['id' => $clinic->id, 'name' => $clinic->name],
            'session' => $session,
            'expected_balance_rial' => $balance,
            'recent_sessions' => ClinicCashRegisterSession::query()->where('doctor_id', $doctor->id)
                ->where('clinic_id', $clinicId)->where('status', 'closed')->latest('closed_at')->limit(10)->get(),
        ]);
    }

    public function open(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        $data = $request->validate([
            'clinic_id' => ['required', 'integer'],
            'opening_balance_rial' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'opening_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $clinicId = (int) $data['clinic_id'];
        $this->authorizeClinicAccess($clinicId, $doctor);
        $userId = (int) Auth::guard('web')->id();

        $session = DB::transaction(function () use ($doctor, $clinicId, $data, $userId): ClinicCashRegisterSession {
            DB::table('addresses')->where('id', $clinicId)->lockForUpdate()->firstOrFail();
            $existing = ClinicCashRegisterSession::query()->where('doctor_id', $doctor->id)
                ->where('clinic_id', $clinicId)->where('status', 'open')->lockForUpdate()->first();
            abort_if($existing, 409, 'برای این شعبه صندوق باز وجود دارد.');

            return ClinicCashRegisterSession::query()->create([
                'doctor_id' => $doctor->id, 'clinic_id' => $clinicId,
                'opened_by_user_id' => $userId,
                'opening_balance_rial' => $data['opening_balance_rial'],
                'status' => 'open', 'opened_at' => now(),
                'opening_note' => $data['opening_note'] ?? null,
            ]);
        }, 3);

        $audit->handle('finance.cash_register.opened', $session, [
            'doctor_id' => $doctor->id, 'clinic_id' => $clinicId,
            'opening_balance_rial' => (int) $session->opening_balance_rial,
        ]);

        return back()->with('success', 'صندوق شعبه باز شد.');
    }

    public function store(
        Request $request,
        ClinicCashRegisterSession $session,
        ClinicAccountingService $accounting,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        abort_unless((int) $session->doctor_id === (int) $doctor->id, 404);
        $this->authorizeClinicAccess((int) $session->clinic_id, $doctor);

        $data = $request->validate([
            'direction' => ['required', Rule::in(['in', 'out'])],
            'category' => ['required', Rule::in(['service_receipt', 'other_receipt', 'operating_expense', 'supplier_payment'])],
            'amount_rial' => ['required', 'integer', 'min:1', 'max:1000000000000'],
            'description' => ['required', 'string', 'min:4', 'max:500'],
            'idempotency_key' => ['required', 'string', 'min:16', 'max:80'],
        ]);
        abort_unless(($data['direction'] === 'in' && in_array($data['category'], ['service_receipt', 'other_receipt'], true))
            || ($data['direction'] === 'out' && in_array($data['category'], ['operating_expense', 'supplier_payment'], true)), 422, 'نوع گردش با دسته انتخاب‌شده سازگار نیست.');

        $userId = (int) Auth::guard('web')->id();
        $transaction = DB::transaction(function () use ($session, $doctor, $data, $userId, $accounting): ClinicCashTransaction {
            $locked = ClinicCashRegisterSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'open', 409, 'صندوق بسته است و ثبت گردش جدید مجاز نیست.');

            $existing = ClinicCashTransaction::query()->where('idempotency_key', $data['idempotency_key'])->first();
            if ($existing) {
                abort_unless(
                    (int) $existing->session_id === (int) $locked->id
                    && (int) $existing->amount_rial === (int) $data['amount_rial']
                    && $existing->direction === $data['direction'],
                    409,
                    'کلید تکرار با تراکنش قبلی و اطلاعات متفاوت استفاده شده است.'
                );
                return $existing;
            }

            $transaction = ClinicCashTransaction::query()->create([
                'session_id' => $locked->id, 'doctor_id' => $doctor->id,
                'clinic_id' => $locked->clinic_id, 'created_by_user_id' => $userId,
                'direction' => $data['direction'], 'category' => $data['category'],
                'amount_rial' => $data['amount_rial'], 'description' => trim($data['description']),
                'idempotency_key' => $data['idempotency_key'],
            ]);

            $organizationId = Schema::hasTable('clinic_organization_clinics')
                ? DB::table('clinic_organization_clinics')->where('clinic_id', $locked->clinic_id)
                    ->where('status', 'active')->value('organization_id')
                : null;

            if ($data['direction'] === 'in') {
                $accounting->recordCashReceipt($organizationId ? (int) $organizationId : null,
                    (int) $locked->clinic_id, (int) $doctor->id, $userId, (int) $transaction->id,
                    (int) $transaction->amount_rial, $transaction->description, now()->toDateTimeString());
            } else {
                $accounting->recordCashDisbursement($organizationId ? (int) $organizationId : null,
                    (int) $locked->clinic_id, (int) $doctor->id, $userId, (int) $transaction->id,
                    (int) $transaction->amount_rial, $transaction->description, now()->toDateTimeString());
            }

            return $transaction;
        }, 3);

        $audit->handle('finance.cash_register.transaction_recorded', $transaction, [
            'doctor_id' => $doctor->id, 'clinic_id' => $transaction->clinic_id,
            'direction' => $transaction->direction, 'amount_rial' => $transaction->amount_rial,
        ]);

        return back()->with('success', 'گردش صندوق و سند حسابداری آن ثبت شد.');
    }

    public function close(
        Request $request,
        ClinicCashRegisterSession $session,
        RecordSecurityAuditEventAction $audit
    ) {
        $doctor = $this->currentDoctor();
        $this->authorizeSecretaryPermission('finance.manage', $doctor);
        abort_unless((int) $session->doctor_id === (int) $doctor->id, 404);
        $this->authorizeClinicAccess((int) $session->clinic_id, $doctor);
        $data = $request->validate([
            'closing_count_rial' => ['required', 'integer', 'min:0', 'max:1000000000000'],
            'closing_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $closed = DB::transaction(function () use ($session, $data): ClinicCashRegisterSession {
            $locked = ClinicCashRegisterSession::query()->whereKey($session->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->status === 'open', 409, 'این صندوق قبلاً بسته شده است.');
            $totals = ClinicCashTransaction::query()->where('session_id', $locked->id)
                ->selectRaw("SUM(CASE WHEN direction = 'in' THEN amount_rial ELSE 0 END) as total_in")
                ->selectRaw("SUM(CASE WHEN direction = 'out' THEN amount_rial ELSE 0 END) as total_out")
                ->first();
            $expected = (int) $locked->opening_balance_rial + (int) ($totals->total_in ?? 0) - (int) ($totals->total_out ?? 0);
            $locked->update([
                'closing_count_rial' => $data['closing_count_rial'],
                'difference_rial' => (int) $data['closing_count_rial'] - $expected,
                'closing_note' => $data['closing_note'] ?? null,
                'closed_by_user_id' => (int) Auth::guard('web')->id(),
                'closed_at' => now(),
                'status' => 'closed',
            ]);
            return $locked->fresh();
        }, 3);

        $audit->handle('finance.cash_register.closed', $closed, [
            'doctor_id' => $doctor->id, 'clinic_id' => $closed->clinic_id,
            'difference_rial' => $closed->difference_rial,
        ]);

        return back()->with('success', 'صندوق بسته شد و مغایرت شمارش با مانده مورد انتظار ثبت شد.');
    }
}
