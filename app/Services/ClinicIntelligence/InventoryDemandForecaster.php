<?php

declare(strict_types=1);

namespace App\Services\ClinicIntelligence;

use Illuminate\Support\Facades\DB;

final class InventoryDemandForecaster
{
    /**
     * Forecasts stock demand from the next appointment horizon and the clinic's
     * own completed-service mix. When no usable service history exists, it
     * falls back to actual stock issues and explicitly lowers confidence.
     */
    public function forecast(int $doctorId, array $clinicIds, int $horizonDays = 30): array
    {
        $horizonDays = max(7, min(90, $horizonDays));
        $from = now()->startOfDay();
        $until = $from->copy()->addDays($horizonDays);
        $historyFrom = $from->copy()->subDays(90);

        if ($clinicIds === []) {
            return [
                'horizon_days' => $horizonDays,
                'generated_at' => now()->toIso8601String(),
                'items' => [],
                'summary' => ['total' => 0, 'critical' => 0, 'warning' => 0, 'insufficient_data' => 0],
            ];
        }

        $items = DB::table('clinic_inventory_items as i')
            ->join('addresses as c', 'c.id', '=', 'i.clinic_id')
            ->where('i.is_active', true)->whereIn('i.clinic_id', $clinicIds)
            ->select('i.*', 'c.name as clinic_name')->orderBy('c.name')->orderBy('i.name')->get();

        $futureAppointments = DB::table('appointments')
            ->select('clinic_id', DB::raw('COUNT(*) as total'))
            ->where('doctor_id', $doctorId)->whereIn('clinic_id', $clinicIds)
            ->whereBetween('appointment_date', [$from->toDateString(), $until->toDateString()])
            ->whereNotIn('status', ['cancelled', 'rejected'])
            ->groupBy('clinic_id')->pluck('total', 'clinic_id');

        $historicalMix = DB::table('patient_treatment_items as ti')
            ->join('patient_treatment_plans as tp', 'tp.id', '=', 'ti.treatment_plan_id')
            ->join('clinic_services as s', 's.id', '=', 'ti.service_id')
            ->where('tp.doctor_id', $doctorId)->whereIn('tp.clinic_id', $clinicIds)
            ->where('ti.stage', 'completed')->where('ti.performed_at', '>=', $historyFrom)
            ->select('tp.clinic_id', 'ti.service_id', 's.specialty_module', DB::raw('COUNT(*) as completed_count'))
            ->groupBy('tp.clinic_id', 'ti.service_id', 's.specialty_module')->get()
            ->groupBy('clinic_id');

        $actualUse = DB::table('clinic_inventory_movements as m')
            ->join('clinic_inventory_items as i', 'i.id', '=', 'm.inventory_item_id')
            ->whereIn('i.clinic_id', $clinicIds)->where('m.movement_type', 'use')
            ->where('m.occurred_at', '>=', $historyFrom)
            ->select('m.inventory_item_id', DB::raw('SUM(m.quantity) as quantity'))
            ->groupBy('m.inventory_item_id')->pluck('quantity', 'inventory_item_id');

        $requirements = DB::table('clinic_service_inventory_requirements as r')
            ->join('clinic_services as s', 's.id', '=', 'r.service_id')
            ->whereIn('s.clinic_id', $clinicIds)
            ->select('s.clinic_id', 's.id as service_id', 's.specialty_module', 'r.inventory_item_id', 'r.quantity')
            ->get()->groupBy('clinic_id');

        $rows = [];
        foreach ($items as $item) {
            $bookedDemand = 0.0;
            $mix = $historicalMix->get($item->clinic_id, collect());
            $mixTotal = (float) $mix->sum('completed_count');
            $futureCount = (int) ($futureAppointments[$item->clinic_id] ?? 0);
            $itemRequirements = $requirements->get($item->clinic_id, collect())
                ->where('inventory_item_id', $item->id);

            if ($mixTotal > 0 && $futureCount > 0) {
                foreach ($itemRequirements as $requirement) {
                    $serviceCount = (float) ($mix->firstWhere('service_id', $requirement->service_id)->completed_count ?? 0);
                    $expectedServiceCount = $futureCount * ($serviceCount / $mixTotal);
                    $bookedDemand += $expectedServiceCount * (float) $requirement->quantity;
                }
            }

            $used90 = (float) ($actualUse[$item->id] ?? 0);
            $fallbackDaily = $used90 / 90;
            $forecastDemand = $bookedDemand > 0 ? $bookedDemand : ($fallbackDaily * $horizonDays);
            $dailyDemand = $forecastDemand / $horizonDays;
            $stock = (float) $item->current_quantity;
            $daysRemaining = $dailyDemand > 0 ? $stock / $dailyDemand : null;
            $lead = max(1, (int) $item->lead_time_days);
            $safety = max(0, (int) $item->safety_stock_days);
            $belowMinimum = $stock <= (float) $item->minimum_quantity;
            $reorderIn = $belowMinimum ? 0 : ($daysRemaining === null ? null : max(0, (int) floor($daysRemaining - $lead - $safety)));
            $targetDays = $lead + $safety + $horizonDays;
            $suggestedQty = max(0, (int) ceil(($dailyDemand * $targetDays) - $stock));
            if ($belowMinimum) {
                $suggestedQty = max($suggestedQty, (int) ceil(((float) $item->minimum_quantity * 2) - $stock));
            }
            $risk = $stock <= 0 ? 'out' : (($daysRemaining !== null && $daysRemaining <= $lead) ? 'critical' :
                (($belowMinimum || ($daysRemaining !== null && $daysRemaining <= ($lead + $safety))) ? 'warning' : 'healthy'));

            $rows[] = [
                'id' => (int) $item->id, 'clinic_id' => (int) $item->clinic_id,
                'clinic_name' => $item->clinic_name, 'name' => $item->name, 'sku' => $item->sku,
                'unit' => $item->unit, 'current_quantity' => (float) $stock,
                'minimum_quantity' => (float) $item->minimum_quantity,
                'lead_time_days' => $lead, 'safety_stock_days' => $safety,
                'preferred_supplier' => $item->preferred_supplier,
                'future_appointments' => $futureCount,
                'forecast_demand' => round($forecastDemand, 3),
                'daily_demand' => round($dailyDemand, 3),
                'days_remaining' => $daysRemaining === null ? null : round($daysRemaining, 1),
                'reorder_in_days' => $reorderIn,
                'suggested_order_quantity' => $suggestedQty,
                'estimated_order_cost' => $suggestedQty * (int) $item->average_unit_cost,
                'risk' => $risk,
                'confidence' => $bookedDemand > 0 ? 'service_mix' : ($used90 > 0 ? 'historical_fallback' : 'insufficient_data'),
                'basis' => $bookedDemand > 0
                    ? 'ترکیب واقعی خدمات تکمیل‌شده در ۹۰ روز گذشته و نوبت‌های آینده'
                    : ($used90 > 0 ? 'میانگین خروج واقعی انبار در ۹۰ روز گذشته' : 'داده کافی برای پیش‌بینی وجود ندارد'),
            ];
        }

        return [
            'horizon_days' => $horizonDays,
            'generated_at' => now()->toIso8601String(),
            'items' => $rows,
            'summary' => [
                'total' => count($rows),
                'critical' => count(array_filter($rows, fn ($row) => in_array($row['risk'], ['out', 'critical'], true))),
                'warning' => count(array_filter($rows, fn ($row) => $row['risk'] === 'warning')),
                'insufficient_data' => count(array_filter($rows, fn ($row) => $row['confidence'] === 'insufficient_data')),
            ],
        ];
    }
}
