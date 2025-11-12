<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="eco-bag-estimator" id="eco-bag-estimator-app"
     data-presets='<?php echo html_escape(json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'
     data-options='<?php echo html_escape(json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'>
    <div class="row">
        <div class="col-md-4">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo _l('eco_bag_estimator'); ?></h4>
                </div>
                <div class="panel-body">
                    <form id="eco-bag-estimator-form">
                        <div class="form-group">
                            <label for="model"><?php echo _l('eco_bag_estimator_field_model'); ?></label>
                            <input type="text" class="form-control" id="model" name="model" required>
                            <small class="text-muted"><?php echo _l('eco_bag_estimator_field_model_hint'); ?></small>
                        </div>
                        <div class="form-group">
                            <label for="preset_selector"><?php echo _l('eco_bag_estimator_field_preset'); ?></label>
                             <select class="form-control" id="preset_selector" name="preset_selector">
                                <option value=""><?php echo _l('eco_bag_estimator_field_preset_placeholder'); ?></option>
                                <?php foreach ($presets as $preset) : ?>
                                    <option value="<?php echo html_escape($preset['model']); ?>">
                                        <?php echo html_escape($preset['model'] . ' — ' . $preset['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="bag_type"><?php echo _l('eco_bag_estimator_field_bag_type'); ?></label>
                            <select class="form-control" id="bag_type" name="bag_type" required>
                                <option value="with_gusset"><?php echo _l('eco_bag_estimator_type_with_gusset'); ?></option>
                                <option value="without_gusset"><?php echo _l('eco_bag_estimator_type_without_gusset'); ?></option>
                                <option value="drawstring"><?php echo _l('eco_bag_estimator_type_drawstring'); ?></option>
                            </select>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="base_cm"><?php echo _l('eco_bag_estimator_field_base'); ?></label>
                                    <input type="number" class="form-control" id="base_cm" name="base_cm" min="0" step="0.01" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="height_cm"><?php echo _l('eco_bag_estimator_field_height'); ?></label>
                                    <input type="number" class="form-control" id="height_cm" name="height_cm" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="row" id="gusset-row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="gusset_cm"><?php echo _l('eco_bag_estimator_field_gusset'); ?></label>
                                    <input type="number" class="form-control" id="gusset_cm" name="gusset_cm" min="0" step="0.01">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="handle_cm"><?php echo _l('eco_bag_estimator_field_handle'); ?></label>
                                    <input type="number" class="form-control" id="handle_cm" name="handle_cm" min="0" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="layout_length_cm"><?php echo _l('eco_bag_estimator_field_layout'); ?></label>
                            <input type="range" class="form-control" id="layout_length_cm" name="layout_length_cm" min="<?php echo (int)$options['eco_bag_layout_length_min_cm']; ?>" max="<?php echo (int)$options['eco_bag_layout_length_max_cm']; ?>" step="1">
                            <div class="slider-value">
                                <span id="layout_length_value"></span> cm
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="quantity"><?php echo _l('eco_bag_estimator_field_quantity'); ?></label>
                            <div class="input-group">
                                <span class="input-group-btn">
                                    <button class="btn btn-default preset-quantity" type="button" data-value="100">100</button>
                                    <button class="btn btn-default preset-quantity" type="button" data-value="500">500</button>
                                    <button class="btn btn-default preset-quantity" type="button" data-value="1000">1000</button>
                                </span>
                                <input type="number" class="form-control" id="quantity" name="quantity" min="1" step="1" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="margin_percentage"><?php echo _l('eco_bag_estimator_field_margin'); ?></label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="margin_percentage" name="margin_percentage" step="0.01" min="0">
                                <span class="input-group-addon">%</span>
                            </div>
                        </div>
                        <div class="form-group text-right">
                            <button type="submit" class="btn btn-primary" id="calculate-button"><?php echo _l('eco_bag_estimator_calculate'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="panel panel-info">
                <div class="panel-heading">
                    <h4 class="panel-title"><?php echo _l('eco_bag_estimator_results_title'); ?></h4>
                </div>
                <div class="panel-body">
                    <div id="eco-bag-results-empty" class="text-center text-muted">
                        <p><?php echo _l('eco_bag_estimator_results_placeholder'); ?></p>
                    </div>
                    <div id="eco-bag-results" class="hidden">
                        <div class="row">
                            <div class="col-sm-6">
                                <h5><?php echo _l('eco_bag_estimator_results_pieces'); ?></h5>
                                <ul class="list-unstyled" id="eco-bag-pieces-list"></ul>
                            </div>
                            <div class="col-sm-6">
                                <h5><?php echo _l('eco_bag_estimator_results_consumption'); ?></h5>
                                <ul class="list-unstyled" id="eco-bag-consumption"></ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <h5><?php echo _l('eco_bag_estimator_results_costs'); ?></h5>
                                <ul class="list-unstyled" id="eco-bag-costs"></ul>
                            </div>
                            <div class="col-sm-6">
                                <h5><?php echo _l('eco_bag_estimator_results_prices'); ?></h5>
                                <ul class="list-unstyled" id="eco-bag-prices"></ul>
                            </div>
                        </div>
                        <div class="text-right actions">
                            <button type="button" class="btn btn-success" id="save-as-product"><?php echo _l('eco_bag_estimator_save_product'); ?></button>
                            <button type="button" class="btn btn-default" id="export-csv"><?php echo _l('eco_bag_estimator_export_csv'); ?></button>
                        </div>
                        <div class="alert alert-danger hidden" id="eco-bag-error"></div>
                        <div class="alert alert-success hidden" id="eco-bag-success"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    window.eco_bag_estimator_translations = <?php echo json_encode([
        'consumption_per_bag' => _l('eco_bag_estimator_consumption_per_bag'),
        'total_area'          => _l('eco_bag_estimator_total_area'),
        'layout_area'         => _l('eco_bag_estimator_layout_area'),
        'layouts'             => _l('eco_bag_estimator_layouts'),
        'linear_meters'       => _l('eco_bag_estimator_linear_meters'),
        'linear_meters_waste' => _l('eco_bag_estimator_linear_meters_waste'),
        'fabric_cost'         => _l('eco_bag_estimator_fabric_cost'),
        'stitch_cost'         => _l('eco_bag_estimator_stitch_cost'),
        'electricity_cost'    => _l('eco_bag_estimator_electricity_cost'),
        'unit_cost'           => _l('eco_bag_estimator_unit_cost'),
        'per_unit'            => _l('eco_bag_estimator_per_unit'),
        'total'               => _l('eco_bag_estimator_total'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
