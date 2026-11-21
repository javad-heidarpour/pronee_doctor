<?php
declare(strict_types=1);
namespace App\Http\Controllers\Portal;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Portal\Concerns\HasActiveDoctor;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
final class ClinicPerformanceController extends Controller
{
  use HasActiveDoctor;
  public function index(Request $request)
  {
    $doctor = $this->currentDoctor();
    $this->authorizeSecretaryPermission('marketing.view', $doctor);
    $ids = $this->accessibleClinicIds($doctor);
    $f = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date'], 'clinic_id' => ['nullable', 'integer']]);
    $to = isset($f['to']) ? Carbon::parse($f['to'])->toDateString() : today()->toDateString();
    $from = isset($f['from']) ? Carbon::parse($f['from'])->toDateString() : today()->subDays(29)->toDateString();
    abort_if($to < $from, 422, 'تاریخ پایان نامعتبر است.');
    abort_if(Carbon::parse($from)->diffInDays(Carbon::parse($to)) > 365, 422, 'بازه گزارش بیش از ۳۶۶ روز است.');
    $clinicId = isset($f['clinic_id']) ? (int) $f['clinic_id'] : null;
    if ($clinicId !== null)
      $this->authorizeClinicAccess($clinicId, $doctor);
    $scope = $clinicId ? [$clinicId] : $ids;
    $clinicsQuery = Address::where('doctor_id', $doctor->id)->whereIn('id', $ids)->where('is_active', true);\n    if ($clinicId !== null) {\n      $clinicsQuery->whereKey($clinicId);\n    }\n    $clinics = $clinicsQuery->orderBy('name')->get(['id', 'name']);
    $q = DB::table('appointments')->where('doctor_id', $doctor->id)->whereIn('clinic_id', $scope)->whereBetween('appointment_date', [$from, $to]);
    $st = (clone $q)->selectRaw('status,COUNT(*) total')->groupBy('status')->pluck('total', 'status');
    $total = (int) $st->sum();
    $visited = (int) ($st['visited'] ?? 0);
    $completed = (int) ($st['completed'] ?? 0);
    $cancelled = (int) ($st['cancelled'] ?? 0) + (int) ($st['canceled'] ?? 0);
    $daily = (clone $q)->selectRaw('appointment_date day,COUNT(*) total')->groupBy('appointment_date')->orderBy('appointment_date')->get()->map(fn($x) => ['day' => (string) $x->day, 'total' => (int) $x->total]);
    $grouped = (clone $q)->select('clinic_id')->selectRaw('COUNT(*) total')
      ->selectRaw("SUM(CASE WHEN status IN ('visited','completed') THEN 1 ELSE 0 END) served")
      ->selectRaw("SUM(CASE WHEN status IN ('cancelled','canceled') THEN 1 ELSE 0 END) cancelled")
      ->selectRaw("SUM(CASE WHEN status IN ('pending','contacted','confirmed') THEN 1 ELSE 0 END) awaiting")->groupBy('clinic_id')->get()->keyBy('clinic_id');
    $payments = DB::table('appointment_payments as p')
      ->join('appointments as a', 'a.id', '=', 'p.appointment_id')
      ->where('a.doctor_id', $doctor->id)->whereIn('a.clinic_id', $scope)
      ->where('p.status', 'paid')
      ->whereBetween('p.paid_at', [Carbon::parse($from)->startOfDay(), Carbon::parse($to)->endOfDay()]);
    $revenue = (int) (clone $payments)->sum('p.amount_rial');
    $paid = (int) (clone $payments)->distinct('p.appointment_id')->count('p.appointment_id');

    $clinicPayments = (clone $payments)->select('a.clinic_id')
      ->selectRaw('SUM(p.amount_rial) gross_paid_rial')
      ->selectRaw('COUNT(DISTINCT p.appointment_id) paid_appointments')
      ->groupBy('a.clinic_id')->get()->keyBy('clinic_id');

    $slots = DB::table('time_slots')->where('doctor_id', $doctor->id)
      ->whereIn('clinic_id', $scope)->whereBetween('date', [$from, $to])
      ->whereNotIn('status', ['cancelled', 'canceled']);
    $slotCount = (int) (clone $slots)->count();
    $occupiedQuery = fn () => (clone $slots)->where(function ($b) {
      $b->whereExists(fn($s) => $s->selectRaw('1')->from('appointments')
        ->whereColumn('appointments.time_slot_id', 'time_slots.id')
        ->whereIn('appointments.status', ['pending', 'contacted', 'confirmed', 'visited', 'completed']))
        ->orWhere(function ($booked) {
          $booked->where('time_slots.status', 'booked')
            ->whereNotExists(fn($s) => $s->selectRaw('1')->from('appointments')
              ->whereColumn('appointments.time_slot_id', 'time_slots.id')
              ->whereIn('appointments.status', ['cancelled', 'canceled', 'failed']));
        });
    });
    $occupied = (int) $occupiedQuery()->count();

    $clinicSlots = (clone $slots)->select('clinic_id')
      ->selectRaw('COUNT(*) total')
      ->selectRaw("SUM(CASE WHEN EXISTS (SELECT 1 FROM appointments ap WHERE ap.time_slot_id=time_slots.id AND ap.status IN ('pending','contacted','confirmed','visited','completed')) OR (status='booked' AND NOT EXISTS (SELECT 1 FROM appointments ax WHERE ax.time_slot_id=time_slots.id AND ax.status IN ('cancelled','canceled','failed'))) THEN 1 ELSE 0 END) occupied")
      ->groupBy('clinic_id')->get()->keyBy('clinic_id');

    $clinicRows = $clinics->map(function ($c) use ($grouped, $clinicPayments, $clinicSlots) {
      $x = $grouped->get($c->id);
      $payment = $clinicPayments->get($c->id);
      $capacity = $clinicSlots->get($c->id);
      $n = (int) ($x->total ?? 0);
      $served = (int) ($x->served ?? 0);
      $slotTotal = (int) ($capacity->total ?? 0);
      $slotOccupied = (int) ($capacity->occupied ?? 0);
      return [
        'id' => $c->id, 'name' => $c->name, 'appointments' => $n, 'served' => $served,
        'cancelled' => (int) ($x->cancelled ?? 0), 'awaiting' => (int) ($x->awaiting ?? 0),
        'completion_rate' => $n ? round($served * 100 / $n, 1) : null,
        'gross_paid_rial' => (int) ($payment->gross_paid_rial ?? 0),
        'paid_appointments' => (int) ($payment->paid_appointments ?? 0),
        'capacity_total' => $slotTotal, 'capacity_occupied' => $slotOccupied,
        'capacity_rate' => $slotTotal ? round(min(100, $slotOccupied * 100 / $slotTotal), 1) : null,
      ];
    });
    return Inertia::render('Portal/ClinicPerformance/Index', [
      'doctor' => $doctor,
      'clinics' => $clinics,
      'filters' => ['from' => $from, 'to' => $to, 'clinic_id' => $clinicId],
      'report' => [
        'appointments' => ['total' => $total, 'served' => $visited + $completed, 'visited' => $visited, 'completed' => $completed, 'cancelled' => $cancelled, 'pending' => (int) ($st['pending'] ?? 0), 'contacted' => (int) ($st['contacted'] ?? 0), 'confirmed' => (int) ($st['confirmed'] ?? 0), 'cancellation_rate' => $total ? round($cancelled * 100 / $total, 1) : null, 'completion_rate' => $total ? round(($visited + $completed) * 100 / $total, 1) : null],
        'capacity' => ['total' => $slotCount, 'occupied' => $occupied, 'available' => max(0, $slotCount - $occupied), 'rate' => $slotCount ? round(min(100, $occupied * 100 / $slotCount), 1) : null],
        'finance' => ['gross_paid_rial' => $revenue, 'paid_appointments' => $paid],
        'clinics' => $clinicRows,
        'daily' => $daily,
        'notes' => ['شاخص‌ها عملیاتی هستند و کیفیت تشخیص یا پیامد درمان را اندازه‌گیری نمی‌کنند.', 'درآمد مجموع پرداخت موفق ناخالص است، نه سود خالص.', 'ظرفیت فقط بر اساس نوبت‌های ایجادشده در بازه محاسبه می‌شود.']
      ]
    ]);
  }
}