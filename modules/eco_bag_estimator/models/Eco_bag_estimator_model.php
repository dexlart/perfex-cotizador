<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Eco_bag_estimator_model extends App_Model
{
    public function get_presets(): array
    {
        $rows = $this->db->order_by('name', 'ASC')->get(db_prefix() . 'eco_bag_estimator_presets')->result_array();
        $presets = [];

        foreach ($rows as $row) {
            $specs = json_decode($row['specs_json'], true) ?: [];
            $presets[] = [
                'model' => $row['model'],
                'name'  => $row['name'],
                'specs' => $specs,
            ];
        }

        return $presets;
    }

    public function get_configuration_options(): array
    {
        $keys = [
            'eco_bag_price_per_meter',
            'eco_bag_stitch_cost_with_gusset',
            'eco_bag_stitch_cost_drawstring',
            'eco_bag_stitch_cost_no_gusset',
            'eco_bag_electricity_per_bag',
            'eco_bag_misc_percentage',
            'eco_bag_general_waste_percentage',
            'eco_bag_fabric_width_m',
            'eco_bag_layout_length_min_cm',
            'eco_bag_layout_length_max_cm',
            'eco_bag_layout_waste_percentage',
            'eco_bag_handle_width_cm',
        ];

        $options = [];
        foreach ($keys as $key) {
            $options[$key] = (float)eco_bag_estimator_option($key, 0);
        }

        return $options;
    }

    public function calculate(array $payload): array
    {
        $model = trim($payload['model'] ?? '');
        $bagType = $payload['bag_type'] ?? '';
        $base = (float)($payload['base_cm'] ?? 0);
        $height = (float)($payload['height_cm'] ?? 0);
        $gusset = (float)($payload['gusset_cm'] ?? 0);
        $handle = (float)($payload['handle_cm'] ?? 0);
        $layoutLength = (float)($payload['layout_length_cm'] ?? eco_bag_estimator_option('eco_bag_layout_length_min_cm', 100));
        $quantity = (int)($payload['quantity'] ?? 0);
        $marginPercentage = eco_bag_estimator_safe_float($payload['margin_percentage'] ?? 0, 0);

        if ($model === '') {
            throw new Exception(_l('eco_bag_estimator_error_model_required'));
        }

        if (!in_array($bagType, ['with_gusset', 'without_gusset', 'drawstring'], true)) {
            throw new Exception(_l('eco_bag_estimator_error_type_required'));
        }

        if ($base <= 0 || $height <= 0 || $handle <= 0 || $quantity <= 0) {
            throw new Exception(_l('eco_bag_estimator_error_positive_values'));
        }

        if ($bagType === 'with_gusset' && $gusset <= 0) {
            throw new Exception(_l('eco_bag_estimator_error_gusset_required'));
        }

        $options = $this->get_configuration_options();
        $layoutLengthMin = $options['eco_bag_layout_length_min_cm'] ?: 100;
        $layoutLengthMax = $options['eco_bag_layout_length_max_cm'] ?: 200;
        $layoutLength = max($layoutLengthMin, min($layoutLength, $layoutLengthMax));

        $preset = $this->find_preset($model);

        $pieces = $this->resolve_pieces($bagType, $base, $height, $gusset, $handle, $preset);
        if (empty($pieces)) {
            throw new Exception(_l('eco_bag_estimator_error_pieces_missing'));
        }

        $areaPerBag = 0.0;
        foreach ($pieces as &$piece) {
            $piece['area_m2'] = eco_bag_estimator_piece_area_m2($piece);
            $areaPerBag += $piece['area_m2'];
        }
        unset($piece);

        $fabricWidth = $options['eco_bag_fabric_width_m'] ?: 1.6;
        $layoutLengthM = eco_bag_estimator_cm_to_m($layoutLength);
        if ($fabricWidth <= 0 || $layoutLengthM <= 0) {
            throw new Exception(_l('eco_bag_estimator_error_layout_dimensions'));
        }

        $areaPerLayout = $fabricWidth * $layoutLengthM;
        $totalArea = $areaPerBag * $quantity;
        $layoutsNeeded = (int)max(1, ceil($totalArea / $areaPerLayout));
        $linearMetersBase = $layoutsNeeded * $layoutLengthM;

        $layoutWastePercentage = $options['eco_bag_layout_waste_percentage'] ?? 0;
        $generalWastePercentage = $options['eco_bag_general_waste_percentage'] ?? 0;
        $wasteMultiplier = (1 + ($layoutWastePercentage / 100));
        $linearMetersWithLayoutWaste = $linearMetersBase * $wasteMultiplier;
        $linearMetersWithTotalWaste = $linearMetersWithLayoutWaste * (1 + ($generalWastePercentage / 100));

        $pricePerMeter = $options['eco_bag_price_per_meter'] ?? 0;
        $pricePerSquareMeter = $fabricWidth > 0 ? $pricePerMeter / $fabricWidth : 0;

        $fabricCostTotal = $linearMetersWithTotalWaste * $pricePerMeter;
        $fabricCostPerBag = $fabricCostTotal / $quantity;

        $stitchCost = $this->resolve_stitch_cost($bagType, $options);
        $electricityCost = $options['eco_bag_electricity_per_bag'] ?? 0;

        $baseProductionCost = $fabricCostPerBag + $stitchCost + $electricityCost;
        $baseProductionCost *= (1 + ($generalWastePercentage / 100));
        $miscPercentage = $options['eco_bag_misc_percentage'] ?? 0;
        $baseProductionCost *= (1 + ($miscPercentage / 100));

        $marginMultiplier = 1 + ($marginPercentage / 100);
        $unitPriceBase = $baseProductionCost * $marginMultiplier;

        $priceForQuantities = $this->build_price_breakdown($unitPriceBase, $quantity);

        $response = [
            'model'                      => $model,
            'bag_type'                   => $bagType,
            'base_cm'                    => $base,
            'height_cm'                  => $height,
            'gusset_cm'                  => $gusset,
            'handle_cm'                  => $handle,
            'layout_length_cm'           => $layoutLength,
            'quantity'                   => $quantity,
            'pieces'                     => $pieces,
            'area_per_bag_m2'            => $areaPerBag,
            'total_area_m2'              => $totalArea,
            'layout_area_m2'             => $areaPerLayout,
            'layouts_needed'             => $layoutsNeeded,
            'linear_meters_base'         => $linearMetersBase,
            'linear_meters_with_waste'   => $linearMetersWithTotalWaste,
            'fabric_cost_per_bag'        => $fabricCostPerBag,
            'fabric_cost_total'          => $fabricCostTotal,
            'stitch_cost_per_bag'        => $stitchCost,
            'electricity_cost_per_bag'   => $electricityCost,
            'misc_percentage'            => $miscPercentage,
            'general_waste_percentage'   => $generalWastePercentage,
            'layout_waste_percentage'    => $layoutWastePercentage,
            'price_per_meter'            => $pricePerMeter,
            'price_per_square_meter'     => $pricePerSquareMeter,
            'unit_cost'                  => $baseProductionCost,
            'unit_price_base'            => $unitPriceBase,
            'price_breakdown'            => $priceForQuantities,
            'margin_percentage'          => $marginPercentage,
            'preset'                     => $preset,
        ];

        return $response;
    }

    public function save_as_product(array $payload): int
    {
        $model = trim($payload['model'] ?? '');
        $bagType = $payload['bag_type'] ?? '';
        $description = trim($payload['description'] ?? '');
        $longDescription = trim($payload['long_description'] ?? '');
        $rate = eco_bag_estimator_safe_float($payload['rate'] ?? 0, 0);
        $sku = trim($payload['sku'] ?? $model);

        if ($model === '' || $description === '' || $rate <= 0) {
            throw new Exception(_l('eco_bag_estimator_error_save_payload'));
        }

        $itemData = [
            'description'      => $description,
            'long_description' => $longDescription,
            'rate'             => $rate,
            'sku'              => $sku,
        ];

        $existing = $this->db->where('sku', $sku)->get(db_prefix() . 'items')->row();
        if ($existing) {
            $this->db->where('id', $existing->id)->update(db_prefix() . 'items', $itemData);
            $itemId = (int)$existing->id;
        } else {
            $this->db->insert(db_prefix() . 'items', $itemData);
            $itemId = (int)$this->db->insert_id();
        }

        if ($bagType !== '') {
            log_message('info', 'Eco Bag Estimator saved product type: ' . $bagType . ' for SKU ' . $sku);
        }

        return $itemId;
    }

    protected function find_preset(string $model): ?array
    {
        if ($model === '') {
            return null;
        }

        $row = $this->db->where('model', $model)->get(db_prefix() . 'eco_bag_estimator_presets')->row_array();
        if (!$row) {
            return null;
        }

        $row['specs_json'] = json_decode($row['specs_json'], true) ?: [];
        $row['hidden_costs_json'] = json_decode($row['hidden_costs_json'], true) ?: [];

        return $row;
    }

    protected function resolve_pieces(string $bagType, float $base, float $height, float $gusset, float $handle, ?array $preset = null): array
    {
        if ($preset && !empty($preset['specs_json']['pieces'])) {
            return $preset['specs_json']['pieces'];
        }

        $pieces = [];
        $seamAllowance = 1.0;
        $topFoldMain = 6.0;
        $bottomAllowanceMain = 2.0;
        $handleWidth = eco_bag_estimator_safe_float(eco_bag_estimator_option('eco_bag_handle_width_cm', 9), 9);

        switch ($bagType) {
            case 'with_gusset':
                $pieces[] = [
                    'label'     => _l('eco_bag_estimator_piece_body'),
                    'width_cm'  => $base + (2 * $seamAllowance),
                    'height_cm' => ($height * 2) + $gusset + $topFoldMain + $bottomAllowanceMain,
                    'quantity'  => 1,
                ];
                $pieces[] = [
                    'label'     => _l('eco_bag_estimator_piece_handle'),
                    'width_cm'  => $handle,
                    'height_cm' => $handleWidth,
                    'quantity'  => 2,
                ];
                $pieces[] = [
                    'label'     => _l('eco_bag_estimator_piece_gusset'),
                    'width_cm'  => $gusset,
                    'height_cm' => $height + 4,
                    'quantity'  => 2,
                ];
                break;
            case 'without_gusset':
                $pieces[] = [
                    'label'     => _l('eco_bag_estimator_piece_body'),
                    'width_cm'  => $base + (2 * $seamAllowance),
                    'height_cm' => ($height * 2) + $topFoldMain + $bottomAllowanceMain,
                    'quantity'  => 1,
                ];
                $pieces[] = [
                    'label'     => _l('eco_bag_estimator_piece_handle'),
                    'width_cm'  => $handle,
                    'height_cm' => $handleWidth,
                    'quantity'  => 2,
                ];
                break;
            case 'drawstring':
                $casingAllowance = 10.0;
                $pieces[] = [
                    'label'     => _l('eco_bag_estimator_piece_body'),
                    'width_cm'  => $base + (2 * $seamAllowance),
                    'height_cm' => $height + $casingAllowance + $bottomAllowanceMain,
                    'quantity'  => 1,
                ];
                $pieces[] = [
                    'label'     => _l('eco_bag_estimator_piece_cord'),
                    'width_cm'  => $handle,
                    'height_cm' => $seamAllowance * 2,
                    'quantity'  => 2,
                ];
                break;
        }

        return $pieces;
    }

    protected function resolve_stitch_cost(string $bagType, array $options): float
    {
        switch ($bagType) {
            case 'with_gusset':
                return $options['eco_bag_stitch_cost_with_gusset'] ?? 0;
            case 'drawstring':
                return $options['eco_bag_stitch_cost_drawstring'] ?? 0;
            case 'without_gusset':
            default:
                return $options['eco_bag_stitch_cost_no_gusset'] ?? 0;
        }
    }

    protected function build_price_breakdown(float $unitPriceBase, int $requestedQuantity): array
    {
        $quantities = [
            100  => 1.00,
            500  => 0.85,
            1000 => 0.75,
        ];

        $result = [];
        foreach ($quantities as $quantity => $discount) {
            $unitPrice = $unitPriceBase * $discount;
            $result[$quantity] = [
                'unit_price'  => $unitPrice,
                'total_price' => $unitPrice * $quantity,
                'discount'    => $discount,
            ];
        }

        if (!isset($result[$requestedQuantity])) {
            $result[$requestedQuantity] = [
                'unit_price'  => $unitPriceBase,
                'total_price' => $unitPriceBase * $requestedQuantity,
                'discount'    => 1.00,
            ];
        }

        return $result;
    }
}
