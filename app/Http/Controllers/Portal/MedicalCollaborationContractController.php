<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Actions\Security\RecordSecurityAuditEventAction;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\MedicalCollaborationContract;
use App\Models\MedicalNetworkProvider;
use App\Models\MedicalReferralSettlement;
use App\Services\MedicalReferralSettlementGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

final class MedicalCollaborationContractController extends Controller
{
    use HasActiveDoctor;

    public function index()
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor', 403);
        $doctor = $this->currentDoctor();

        $contracts = MedicalCollaborationContract::query()
            ->where('doctor_id', $doctor->id)
            ->with('provider:id,name,provider_type,city')
            ->withCount('settlements')
            ->withSum('settlements', 'amount_rial')
            ->latest()->get();

        $incoming = MedicalCollaborationContract::query()
            ->whereHas('provider', fn ($q) => $q->where('created_by_user_id', $user->id))
            ->with('doctor:id,name,family')
            ->with('provider:id,name')
            ->latest()->get();

        $settlements = MedicalReferralSettlement::query()
            ->whereHas('contract', fn ($q) => $q->where('doctor_id', $doctor->id))
            ->with(['contract.provider:id,name', 'referral:id,tracking_code,completed_at'])
            ->latest()->limit(100)->get();

        return Inertia::render('Portal/MedicalNetwork/Contracts', [
            'contracts' => $contracts,
            'incoming' => $incoming,
            'settlements' => $settlements,
            'providers' => MedicalNetworkProvider::query()->where('status', 'active')->where('is_verified', true)
                ->orderBy('name')->get(['id', 'name', 'provider_type', 'city']),
        ]);
    }

    public function store(Request $request, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor', 403);
        $doctor = $this->currentDoctor();

        $data = $request->validate([
            'provider_id' => ['required', 'integer', Rule::exists('medical_network_providers', 'id')->where('status', 'active')->where('is_verified', true)],
            'service_type' => ['required', Rule::in(['laboratory', 'imaging', 'rehabilitation', 'consultation'])],
            'unit_fee_rial' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'starts_at' => ['required', 'date', 'after_or_equal:today'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'terms' => ['nullable', 'string', 'max:3000'],
        ]);

        $provider = MedicalNetworkProvider::query()->findOrFail($data['provider_id']);
        abort_if((int) $provider->doctor_id === (int) $doctor->id, 422, 'امکان عقد قرارداد با پروفایل خودتان وجود ندارد.');
        $expected = $provider->provider_type === 'doctor' ? 'consultation' : $provider->provider_type;
        abort_unless($expected === $data['service_type'], 422, 'نوع خدمت با نوع مرکز مقصد همخوانی ندارد.');

        $contract = MedicalCollaborationContract::query()->create([
            ...$data,
            'doctor_id' => $doctor->id,
            'created_by_user_id' => $user->id,
            'status' => 'proposed',
        ]);
        $audit->handle('medical_network.contract_proposed', $contract, ['doctor_id' => $doctor->id, 'changed_fields' => ['contract_proposed']]);

        return back()->with('success', 'پیشنهاد قرارداد ثبت شد؛ تا زمان پذیرش طرف مقابل فعال نخواهد بود.');
    }

    public function respond(Request $request, MedicalCollaborationContract $contract, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user, 401);
        $data = $request->validate(['decision' => ['required', Rule::in(['accept', 'reject'])]]);

        DB::transaction(function () use ($contract, $user, $data): void {
            $locked = MedicalCollaborationContract::query()->whereKey($contract->id)->lockForUpdate()->with('provider')->firstOrFail();
            abort_unless((int) $locked->provider?->created_by_user_id === (int) $user->id, 403);
            abort_unless($locked->status === 'proposed', 422, 'این پیشنهاد قبلاً بررسی شده است.');

            $locked->update([
                'status' => $data['decision'] === 'accept' ? 'active' : 'rejected',
                'accepted_at' => $data['decision'] === 'accept' ? now() : null,
            ]);
        });

        $audit->handle('medical_network.contract_responded', $contract, ['changed_fields' => ['status_changed']]);
        return back()->with('success', $data['decision'] === 'accept' ? 'قرارداد پذیرفته و فعال شد.' : 'پیشنهاد قرارداد رد شد.');
    }

    public function generate(Request $request, MedicalReferralSettlementGenerator $generator, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && in_array($user->role, ['doctor', 'admin'], true), 403);
        $doctor = $this->currentDoctor();
        $count = $generator->generateForDoctor((int) $doctor->id);
        if ($count > 0) {
            $audit->handle('medical_network.settlements_generated', null, [
                'doctor_id' => $doctor->id,
                'changed_fields' => ['settlements_generated'],
            ]);
        }

        return back()->with('success', $count . ' ردیف تسویه جدید بر اساس ارجاعات تکمیل‌شده ایجاد شد.');
    }

    public function markPaid(Request $request, MedicalReferralSettlement $settlement, RecordSecurityAuditEventAction $audit)
    {
        $user = Auth::guard('web')->user();
        abort_unless($user && $user->role === 'doctor', 403);
        $doctor = $this->currentDoctor();

        DB::transaction(function () use ($settlement, $user, $doctor): void {
            $locked = MedicalReferralSettlement::query()->whereKey($settlement->id)->lockForUpdate()->with('contract')->firstOrFail();
            abort_unless(
                (int) $locked->contract?->doctor_id === (int) $doctor->id
                && (int) $locked->contract?->created_by_user_id === (int) $user->id,
                403
            );
            abort_unless($locked->status === 'pending', 422, 'این ردیف قبلاً تسویه شده است.');
            $locked->update(['status' => 'paid', 'paid_at' => now(), 'paid_by_user_id' => $user->id]);
        });

        $audit->handle('medical_network.settlement_paid', $settlement, [
            'doctor_id' => $doctor->id,
            'changed_fields' => ['status_changed'],
        ]);
        return back()->with('success', 'پرداخت ثبت شد. ثبت پرداخت به معنی تأیید بانکی یا اتصال به درگاه نیست.');
    }
}
