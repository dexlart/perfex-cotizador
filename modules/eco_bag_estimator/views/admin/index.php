<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="eco-bag-estimator" id="eco-bag-estimator-app"
     data-presets='<?php echo html_escape(json_encode($presets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'
     data-options='<?php echo html_escape(json_encode($options, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>'
     data-can-edit-options="<?php echo $can_edit_options ? '1' : '0'; ?>">
    <div class="row eco-bag-layout">
        <div class="col-lg-4 col-md-5">
            <div class="eco-bag-card eco-bag-card-form">
                <div class="eco-bag-card-header">
                    <h4 class="eco-bag-card-title"><?php echo _l('eco_bag_estimator'); ?></h4>
                    <p class="eco-bag-card-subtitle text-muted"><?php echo _l('eco_bag_estimator_form_subtitle'); ?></p>
                </div>
                <div class="eco-bag-card-body">
                    <form id="eco-bag-estimator-form" class="eco-bag-form">
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
        <div class="col-lg-8 col-md-7">
            <div class="eco-bag-card eco-bag-card-options">
                <div class="eco-bag-card-header">
                    <h4 class="eco-bag-card-title"><?php echo _l('eco_bag_estimator_options_title'); ?></h4>
                    <p class="eco-bag-card-subtitle text-muted"><?php echo _l('eco_bag_estimator_options_subtitle'); ?></p>
                </div>
                <div class="eco-bag-card-body">
                    <div class="eco-bag-metric-grid" id="eco-bag-option-metrics">
                        <div class="eco-bag-metric" data-option="eco_bag_price_per_meter" data-type="currency">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_price_per_meter'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_fabric_width_m" data-type="number" data-suffix=" m">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_fabric_width'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_layout_waste_percentage" data-type="percentage">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_layout_waste'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_general_waste_percentage" data-type="percentage">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_general_waste'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_misc_percentage" data-type="percentage">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_misc_percentage'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_electricity_per_bag" data-type="currency">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_electricity'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_stitch_cost_with_gusset" data-type="currency">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_stitch_with_gusset'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_stitch_cost_no_gusset" data-type="currency">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_stitch_without_gusset'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_stitch_cost_drawstring" data-type="currency">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_stitch_drawstring'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                        <div class="eco-bag-metric" data-option="eco_bag_handle_width_cm" data-type="number" data-suffix=" cm">
                            <span class="eco-bag-metric-label"><?php echo _l('eco_bag_estimator_option_handle_width'); ?></span>
                            <span class="eco-bag-metric-value"></span>
                        </div>
                    </div>

                    <?php if ($can_edit_options) : ?>
                        <div class="eco-bag-options-divider"></div>
                        <p class="text-muted"><?php echo _l('eco_bag_estimator_options_hint'); ?></p>
                        <div id="eco-bag-options-feedback" class="alert hidden"></div>
                        <form id="eco-bag-options-form" class="eco-bag-options-form">
                            <div class="row">
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_price_per_meter"><?php echo _l('eco_bag_estimator_option_price_per_meter'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_price_per_meter" id="eco_bag_price_per_meter">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_fabric_width_m"><?php echo _l('eco_bag_estimator_option_fabric_width'); ?></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_fabric_width_m" id="eco_bag_fabric_width_m">
                                            <span class="input-group-addon">m</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_stitch_cost_with_gusset"><?php echo _l('eco_bag_estimator_option_stitch_with_gusset'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_stitch_cost_with_gusset" id="eco_bag_stitch_cost_with_gusset">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_stitch_cost_no_gusset"><?php echo _l('eco_bag_estimator_option_stitch_without_gusset'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_stitch_cost_no_gusset" id="eco_bag_stitch_cost_no_gusset">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_stitch_cost_drawstring"><?php echo _l('eco_bag_estimator_option_stitch_drawstring'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_stitch_cost_drawstring" id="eco_bag_stitch_cost_drawstring">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_electricity_per_bag"><?php echo _l('eco_bag_estimator_option_electricity'); ?></label>
                                        <div class="input-group">
                                            <span class="input-group-addon">$</span>
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_electricity_per_bag" id="eco_bag_electricity_per_bag">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_misc_percentage"><?php echo _l('eco_bag_estimator_option_misc_percentage'); ?></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_misc_percentage" id="eco_bag_misc_percentage">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_general_waste_percentage"><?php echo _l('eco_bag_estimator_option_general_waste'); ?></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_general_waste_percentage" id="eco_bag_general_waste_percentage">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_layout_waste_percentage"><?php echo _l('eco_bag_estimator_option_layout_waste'); ?></label>
                                        <div class="input-group">
                                            <input type="number" step="0.01" min="0" class="form-control" name="eco_bag_layout_waste_percentage" id="eco_bag_layout_waste_percentage">
                                            <span class="input-group-addon">%</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_handle_width_cm"><?php echo _l('eco_bag_estimator_option_handle_width'); ?></label>
                                        <div class="input-group">
                                            <input type="number" step="0.1" min="0" class="form-control" name="eco_bag_handle_width_cm" id="eco_bag_handle_width_cm">
                                            <span class="input-group-addon">cm</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_layout_length_min_cm"><?php echo _l('eco_bag_estimator_option_layout_min'); ?></label>
                                        <div class="input-group">
                                            <input type="number" step="1" min="0" class="form-control" name="eco_bag_layout_length_min_cm" id="eco_bag_layout_length_min_cm">
                                            <span class="input-group-addon">cm</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="form-group">
                                        <label for="eco_bag_layout_length_max_cm"><?php echo _l('eco_bag_estimator_option_layout_max'); ?></label>
                                        <div class="input-group">
                                            <input type="number" step="1" min="0" class="form-control" name="eco_bag_layout_length_max_cm" id="eco_bag_layout_length_max_cm">
                                            <span class="input-group-addon">cm</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-right">
                                <button type="submit" class="btn btn-primary" id="eco-bag-options-save"><?php echo _l('eco_bag_estimator_options_save'); ?></button>
                            </div>
                        </form>
                    <?php else : ?>
                        <div class="alert alert-info eco-bag-options-alert" role="alert"><?php echo _l('eco_bag_estimator_options_disabled'); ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="eco-bag-card eco-bag-card-results">
                <div class="eco-bag-card-header">
                    <h4 class="eco-bag-card-title"><?php echo _l('eco_bag_estimator_results_title'); ?></h4>
                </div>
                <div class="eco-bag-card-body">
                    <div id="eco-bag-results-empty" class="eco-bag-empty-state text-center text-muted">
                        <p><?php echo _l('eco_bag_estimator_results_placeholder'); ?></p>
                    </div>
                    <div id="eco-bag-results" class="hidden">
                        <div class="row">
                            <div class="col-sm-6">
                                <h5 class="eco-bag-section-title"><?php echo _l('eco_bag_estimator_results_pieces'); ?></h5>
                                <ul class="eco-bag-list" id="eco-bag-pieces-list"></ul>
                            </div>
                            <div class="col-sm-6">
                                <h5 class="eco-bag-section-title"><?php echo _l('eco_bag_estimator_results_consumption'); ?></h5>
                                <ul class="eco-bag-list" id="eco-bag-consumption"></ul>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6">
                                <h5 class="eco-bag-section-title"><?php echo _l('eco_bag_estimator_results_costs'); ?></h5>
                                <ul class="eco-bag-list" id="eco-bag-costs"></ul>
                            </div>
                            <div class="col-sm-6">
                                <h5 class="eco-bag-section-title"><?php echo _l('eco_bag_estimator_results_prices'); ?></h5>
                                <ul class="eco-bag-list" id="eco-bag-prices"></ul>
                            </div>
                        </div>
                        <div class="eco-bag-actions text-right">
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
        'consumption_per_bag'      => _l('eco_bag_estimator_consumption_per_bag'),
        'total_area'               => _l('eco_bag_estimator_total_area'),
        'layout_area'              => _l('eco_bag_estimator_layout_area'),
        'layouts'                  => _l('eco_bag_estimator_layouts'),
        'linear_meters'            => _l('eco_bag_estimator_linear_meters'),
        'linear_meters_waste'      => _l('eco_bag_estimator_linear_meters_waste'),
        'fabric_cost'              => _l('eco_bag_estimator_fabric_cost'),
        'stitch_cost'              => _l('eco_bag_estimator_stitch_cost'),
        'electricity_cost'         => _l('eco_bag_estimator_electricity_cost'),
        'unit_cost'                => _l('eco_bag_estimator_unit_cost'),
        'per_unit'                 => _l('eco_bag_estimator_per_unit'),
        'total'                    => _l('eco_bag_estimator_total'),
        'price_per_meter'          => _l('eco_bag_estimator_price_per_meter'),
        'price_per_square_meter'   => _l('eco_bag_estimator_price_per_square_meter'),
        'fabric_width'             => _l('eco_bag_estimator_fabric_width'),
        'misc_percentage'          => _l('eco_bag_estimator_misc_percentage'),
        'general_waste'            => _l('eco_bag_estimator_general_waste'),
        'layout_waste'             => _l('eco_bag_estimator_layout_waste'),
        'margin'                   => _l('eco_bag_estimator_margin'),
        'options_saved'            => _l('eco_bag_estimator_options_saved'),
        'options_saving'           => _l('eco_bag_estimator_options_saving'),
        'options_error'            => _l('eco_bag_estimator_options_error'),
        'generic_error'            => _l('eco_bag_estimator_generic_error'),
        'price_per_meter_suffix'   => _l('eco_bag_estimator_price_per_meter_suffix'),
        'price_per_square_suffix'  => _l('eco_bag_estimator_price_per_square_suffix'),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
</script>
