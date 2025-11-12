<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Eco_bag_estimator_model extends App_Model
{
    public function calculate(array $input): array
    {
        $model = $input['model'] ?? null;
        $modelData = eco_bag_estimator_model($model);

        if (!$modelData) {
            throw new \InvalidArgumentException('Modelo no válido');
        }

        $quantity = (int)($input['quantity'] ?? 0);
        $rollWidth = (float)($input['fabric_roll_width_cm'] ?? 0);
        $rollLength = (float)($input['fabric_roll_length_m'] ?? 0);
        $fabricCost = (float)($input['fabric_cost_per_meter'] ?? $modelData['default_fabric_cost_per_meter']);
        $threadPerBag = (float)($input['thread_per_bag_m'] ?? $modelData['default_thread_per_bag_m']);
        $threadSpoolLength = (float)($input['thread_spool_length_m'] ?? $modelData['default_thread_spool_length_m']);
        $threadSpoolCost = (float)($input['thread_spool_cost'] ?? $modelData['default_thread_spool_cost']);
        $laborCostPerBag = (float)($input['labor_cost_per_bag'] ?? $modelData['default_labor_cost_per_bag']);
        $setupCosts = (float)($input['additional_costs'] ?? 0);

        $panelWidth = (float)$modelData['panel_width_cm'];
        $panelHeight = (float)$modelData['panel_height_cm'];
        $gusset = (float)$modelData['gusset_cm'];
        $foldAllowance = (float)$modelData['fold_allowance_cm'];
        $seamAllowance = (float)$modelData['seam_allowance_cm'];

        $usableBagWidth = $panelWidth + (2 * $gusset) + (2 * $seamAllowance);
        $fabricWidthUsage = $usableBagWidth;
        $fabricLengthPerBag = ($panelHeight + $foldAllowance + $seamAllowance) / 100; // metros

        $panelsPerLay = $rollWidth > 0 ? (int)floor($rollWidth / $fabricWidthUsage) : 0;
        $panelsPerLay = max($panelsPerLay, 1);
        $layCount = $quantity > 0 ? (int)ceil($quantity / $panelsPerLay) : 0;

        $fabricRequiredMeters = $quantity * $fabricLengthPerBag;
        $fabricRequiredRolls = $rollLength > 0 ? $fabricRequiredMeters / $rollLength : 0;

        $fabricWastePerLayWidth = $rollWidth - ($panelsPerLay * $fabricWidthUsage);
        $fabricWastePercent = $rollWidth > 0 ? max($fabricWastePerLayWidth, 0) / $rollWidth * 100 : 0;

        $fabricWasteMeters = ($fabricWastePerLayWidth > 0)
            ? ($fabricWastePerLayWidth / 100) * ($layCount * $fabricLengthPerBag)
            : 0;

        $threadTotalMeters = $threadPerBag * $quantity;
        $threadSpoolsNeeded = $threadSpoolLength > 0 ? $threadTotalMeters / $threadSpoolLength : 0;

        $fabricCostTotal = $fabricRequiredMeters * $fabricCost;
        $threadCostTotal = $threadSpoolLength > 0 ? ceil($threadSpoolsNeeded) * $threadSpoolCost : 0;
        $laborCostTotal = $laborCostPerBag * $quantity;
        $materialWasteCost = $fabricCost * $fabricWasteMeters;
        $totalCost = $fabricCostTotal + $threadCostTotal + $laborCostTotal + $setupCosts + $materialWasteCost;

        $unitCost = $quantity > 0 ? $totalCost / $quantity : 0;

        return [
            'model' => $modelData,
            'quantity' => $quantity,
            'fabric_width_usage_cm' => round($fabricWidthUsage, 2),
            'fabric_length_per_bag_m' => round($fabricLengthPerBag, 3),
            'panels_per_lay' => $panelsPerLay,
            'lay_count' => $layCount,
            'fabric_required_meters' => round($fabricRequiredMeters, 2),
            'fabric_required_rolls' => round($fabricRequiredRolls, 2),
            'fabric_waste_percent' => round($fabricWastePercent, 2),
            'fabric_waste_meters' => round($fabricWasteMeters, 2),
            'thread_total_meters' => round($threadTotalMeters, 2),
            'thread_spools_needed' => ceil($threadSpoolsNeeded),
            'fabric_cost_total' => round($fabricCostTotal, 2),
            'thread_cost_total' => round($threadCostTotal, 2),
            'labor_cost_total' => round($laborCostTotal, 2),
            'material_waste_cost' => round($materialWasteCost, 2),
            'setup_costs' => round($setupCosts, 2),
            'total_cost' => round($totalCost, 2),
            'unit_cost' => round($unitCost, 4),
            'input' => [
                'fabric_roll_width_cm' => $rollWidth,
                'fabric_roll_length_m' => $rollLength,
                'fabric_cost_per_meter' => $fabricCost,
                'thread_per_bag_m' => $threadPerBag,
                'thread_spool_length_m' => $threadSpoolLength,
                'thread_spool_cost' => $threadSpoolCost,
                'labor_cost_per_bag' => $laborCostPerBag,
                'additional_costs' => $setupCosts,
            ],
        ];
    }
}
