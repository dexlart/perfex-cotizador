<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
$defaults = $selectedProduct['defaults'] ?? [];
$fieldValue = function (string $key, $fallback = null) use ($formData, $defaults) {
    if (isset($formData[$key]) && $formData[$key] !== '') {
        return $formData[$key];
    }

    return $defaults[$key] ?? $fallback;
};

$modelOptions = [];
foreach ($models as $slug => $model) {
    $modelOptions[] = ['id' => $slug, 'name' => $model['name']];
}

$selectedModelKey = $formData['model'] ?? ($selectedProduct['model'] ?? '');
$modelInfo = $selectedModelKey && isset($models[$selectedModelKey])
    ? array_merge(['slug' => $selectedModelKey], $models[$selectedModelKey])
    : null;

$bagWidth = $modelInfo
    ? $modelInfo['panel_width_cm'] + (2 * $modelInfo['gusset_cm']) + (2 * $modelInfo['seam_allowance_cm'])
    : 0;
$bagHeight = $modelInfo
    ? $modelInfo['panel_height_cm'] + $modelInfo['fold_allowance_cm'] + $modelInfo['seam_allowance_cm']
    : 0;
$rollWidth = (float)$fieldValue('fabric_roll_width_cm', $bagWidth ? max($bagWidth + 20, 160) : 160);
$quantityValue = (int)$fieldValue('quantity', 1000);
$fabricLengthPerBag = $result['fabric_length_per_bag_m'] ?? ($bagHeight ? round($bagHeight / 100, 3) : 0);
$panelsPerLay = $result['panels_per_lay'] ?? null;
$layCount = $result['lay_count'] ?? null;
?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content eco-bag-estimator-page">
        <?php echo form_open(admin_url('eco_bag_estimator'), ['method' => 'post', 'id' => 'ecoBagEstimatorForm']); ?>
        <div class="eco-bag-layout">
            <aside class="eco-bag-layout__sidebar panel_s">
                <div class="panel-body">
                    <h4 class="_title"><?php echo _l('eco_bag_estimator_library_title'); ?></h4>
                    <p class="text-muted"><?php echo _l('eco_bag_estimator_library_description'); ?></p>
                    <div class="eco-bag-search">
                        <span class="fa fa-search"></span>
                        <input type="search" class="form-control" id="ecoBagProductSearch" placeholder="<?php echo _l('eco_bag_estimator_search_products'); ?>">
                    </div>
                    <div class="eco-bag-product-list" id="ecoBagProductList">
                        <?php foreach ($productsByCategory as $category => $payload) : ?>
                            <div class="eco-bag-product-category" data-category="<?php echo html_escape($category); ?>">
                                <h5><?php echo html_escape($payload['name']); ?></h5>
                                <ul class="list-unstyled">
                                    <?php foreach ($payload['items'] as $slug => $product) :
                                        $isActive = ($formData['product_slug'] ?? $selectedProduct['slug'] ?? '') === $slug;
                                        $defaultsJson = html_escape(json_encode($product['defaults'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                    ?>
                                    <li class="eco-bag-product <?php echo $isActive ? 'is-active' : ''; ?>" data-product="<?php echo html_escape($slug); ?>" data-model="<?php echo html_escape($product['model']); ?>" data-defaults="<?php echo $defaultsJson; ?>" data-notes="<?php echo html_escape($product['notes'] ?? ''); ?>" data-url="<?php echo html_escape($product['url'] ?? ''); ?>" data-name="<?php echo html_escape($product['name']); ?>" data-description="<?php echo html_escape($product['short_description'] ?? ''); ?>">
                                        <span class="eco-bag-product__name"><?php echo html_escape($product['name']); ?></span>
                                        <?php if (!empty($product['short_description'])) : ?>
                                            <small class="text-muted d-block"><?php echo html_escape($product['short_description']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <input type="hidden" name="product_slug" id="ecoBagProductSlug" value="<?php echo html_escape($formData['product_slug'] ?? $selectedProduct['slug'] ?? ''); ?>">
                </div>
            </aside>
            <div class="eco-bag-layout__main">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="_title"><?php echo _l('eco_bag_estimator_title'); ?></h4>
                        <p class="text-muted"><?php echo _l('eco_bag_estimator_description'); ?></p>
                        <div class="eco-bag-model-summary">
                            <div class="eco-bag-summary-card">
                                <span class="eco-bag-summary__label"><?php echo _l('eco_bag_estimator_current_model'); ?></span>
                                <span class="eco-bag-summary__value" id="ecoBagSelectedModelLabel" data-empty="<?php echo html_escape(_l('eco_bag_estimator_no_model_selected')); ?>"><?php echo html_escape($modelInfo['name'] ?? _l('eco_bag_estimator_no_model_selected')); ?></span>
                                <?php if (!empty($modelInfo['handle_type'])) : ?>
                                    <span class="eco-bag-summary__meta" id="ecoBagHandleType"><?php echo html_escape($modelInfo['handle_type']); ?></span>
                                <?php else : ?>
                                    <span class="eco-bag-summary__meta" id="ecoBagHandleType">&nbsp;</span>
                                <?php endif; ?>
                            </div>
                            <div class="eco-bag-summary-card">
                                <span class="eco-bag-summary__label"><?php echo _l('eco_bag_estimator_dimensions'); ?></span>
                                <span class="eco-bag-summary__value" id="ecoBagModelDimensions"><?php echo $modelInfo ? app_format_number($modelInfo['panel_width_cm']) . ' × ' . app_format_number($modelInfo['panel_height_cm']) . ' cm' : '--'; ?></span>
                                <span class="eco-bag-summary__meta" id="ecoBagModelExtra" data-help="<?php echo html_escape(_l('eco_bag_estimator_dimensions_help')); ?>"><?php echo $modelInfo ? _l('eco_bag_estimator_dimensions_detail', app_format_number($modelInfo['gusset_cm'])) : _l('eco_bag_estimator_dimensions_help'); ?></span>
                            </div>
                            <div class="eco-bag-summary-card">
                                <span class="eco-bag-summary__label"><?php echo _l('eco_bag_estimator_weight'); ?></span>
                                <span class="eco-bag-summary__value" id="ecoBagModelWeight"><?php echo $modelInfo ? app_format_number($modelInfo['fabric_weight_gsm']) . ' g/m²' : '--'; ?></span>
                                <span class="eco-bag-summary__meta"><?php echo _l('eco_bag_estimator_weight_help'); ?></span>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_select('model', $modelOptions, ['id', 'name'], _l('eco_bag_estimator_select_model'), $selectedModelKey); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('quantity', _l('eco_bag_estimator_quantity'), $quantityValue, 'number', ['min' => 1, 'id' => 'ecoBagQuantity']); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <?php echo render_input('fabric_roll_width_cm', _l('eco_bag_estimator_fabric_roll_width'), $rollWidth, 'number', ['step' => '0.1', 'min' => 1, 'id' => 'ecoBagRollWidth']); ?>
                            </div>
                            <div class="col-md-6">
                                <?php echo render_input('fabric_roll_length_m', _l('eco_bag_estimator_fabric_roll_length'), $fieldValue('fabric_roll_length_m', 500), 'number', ['step' => '0.01', 'min' => 1]); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?php echo render_input('fabric_cost_per_meter', _l('eco_bag_estimator_fabric_cost'), $fieldValue('fabric_cost_per_meter', $modelInfo['default_fabric_cost_per_meter'] ?? ''), 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('labor_cost_per_bag', _l('eco_bag_estimator_labor_cost'), $fieldValue('labor_cost_per_bag', $modelInfo['default_labor_cost_per_bag'] ?? ''), 'number', ['step' => '0.0001', 'min' => 0]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('additional_costs', _l('eco_bag_estimator_additional_costs'), $fieldValue('additional_costs', 0), 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?php echo render_input('thread_per_bag_m', _l('eco_bag_estimator_thread_per_bag'), $fieldValue('thread_per_bag_m', $modelInfo['default_thread_per_bag_m'] ?? ''), 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('thread_spool_length_m', _l('eco_bag_estimator_thread_spool_length'), $fieldValue('thread_spool_length_m', $modelInfo['default_thread_spool_length_m'] ?? ''), 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('thread_spool_cost', _l('eco_bag_estimator_thread_spool_cost'), $fieldValue('thread_spool_cost', $modelInfo['default_thread_spool_cost'] ?? ''), 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                        </div>
                        <div class="text-right eco-bag-submit">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-calculator"></i> <?php echo _l('eco_bag_estimator_calculate'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <section class="eco-bag-layout__preview">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="_title"><?php echo _l('eco_bag_estimator_layout_preview'); ?></h4>
                        <p class="text-muted"><?php echo _l('eco_bag_estimator_layout_description'); ?></p>
                        <canvas id="ecoBagLayPreview" width="620" height="320"
                            data-bag-width="<?php echo html_escape($bagWidth); ?>"
                            data-bag-height="<?php echo html_escape($bagHeight); ?>"
                            data-roll-width="<?php echo html_escape($rollWidth); ?>"
                            data-quantity="<?php echo html_escape($quantityValue); ?>"
                            data-panels-per-lay="<?php echo html_escape($panelsPerLay ?? ''); ?>"
                            data-lay-count="<?php echo html_escape($layCount ?? ''); ?>"
                            data-fabric-length-per-bag="<?php echo html_escape($fabricLengthPerBag); ?>"></canvas>
                        <div class="eco-bag-preview__legend">
                            <div>
                                <span class="legend-color legend-color--panel"></span>
                                <span><?php echo _l('eco_bag_estimator_layout_piece'); ?></span>
                            </div>
                            <div>
                                <span class="legend-color legend-color--waste"></span>
                                <span><?php echo _l('eco_bag_estimator_layout_waste'); ?></span>
                            </div>
                        </div>
                        <div class="eco-bag-preview__meta" id="ecoBagPreviewMeta">
                            <div>
                                <strong><?php echo _l('eco_bag_estimator_layout_roll_width'); ?>:</strong>
                                <span id="ecoBagPreviewRollWidth"><?php echo app_format_number($rollWidth); ?> cm</span>
                            </div>
                            <div>
                                <strong><?php echo _l('eco_bag_estimator_layout_panel_width'); ?>:</strong>
                                <span id="ecoBagPreviewPanelWidth"><?php echo $bagWidth ? app_format_number($bagWidth) . ' cm' : '--'; ?></span>
                            </div>
                            <div>
                                <strong><?php echo _l('eco_bag_estimator_layout_panel_height'); ?>:</strong>
                                <span id="ecoBagPreviewPanelHeight"><?php echo $bagHeight ? app_format_number($bagHeight) . ' cm' : '--'; ?></span>
                            </div>
                            <div>
                                <strong><?php echo _l('eco_bag_estimator_panels_per_lay'); ?>:</strong>
                                <span id="ecoBagPreviewPanelsPerLay"><?php echo $panelsPerLay !== null ? app_format_number($panelsPerLay) : '--'; ?></span>
                            </div>
                            <div>
                                <strong><?php echo _l('eco_bag_estimator_lay_count'); ?>:</strong>
                                <span id="ecoBagPreviewLayCount"><?php echo $layCount !== null ? app_format_number($layCount) : '--'; ?></span>
                            </div>
                        </div>
                        <div class="eco-bag-product-detail" id="ecoBagSelectedProductDetail" data-selected-product="<?php echo html_escape(json_encode($selectedProduct ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)); ?>">
                            <h5><?php echo _l('eco_bag_estimator_selected_product'); ?></h5>
                            <p class="eco-bag-product-detail__name" id="ecoBagProductName"><?php echo html_escape($selectedProduct['name'] ?? _l('eco_bag_estimator_no_product_selected')); ?></p>
                            <p class="text-muted" id="ecoBagProductDescription"><?php echo html_escape($selectedProduct['short_description'] ?? ''); ?></p>
                            <p class="eco-bag-product-detail__notes" id="ecoBagProductNotes"><?php echo html_escape($selectedProduct['notes'] ?? ''); ?></p>
                            <a id="ecoBagProductLink" href="<?php echo !empty($selectedProduct['url']) ? html_escape($selectedProduct['url']) : '#'; ?>" target="_blank" rel="noopener" class="eco-bag-product-detail__link<?php echo empty($selectedProduct['url']) ? ' is-hidden' : ''; ?>">
                                <i class="fa fa-external-link"></i> <?php echo _l('eco_bag_estimator_open_product'); ?>
                            </a>
                        </div>
                        <div class="alert alert-info eco-bag-preview__note">
                            <i class="fa fa-info-circle"></i> <?php echo _l('eco_bag_estimator_layout_help'); ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <?php echo form_close(); ?>

        <?php if (!empty($result)) : ?>
        <div class="eco-bag-results">
            <div class="row">
                <div class="col-md-6">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="_title"><?php echo _l('eco_bag_estimator_materials'); ?></h4>
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_fabric_width_usage'); ?></td>
                                        <td id="ecoBagResultFabricWidth"><?php echo app_format_number($result['fabric_width_usage_cm']); ?> cm</td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_fabric_length_per_bag'); ?></td>
                                        <td id="ecoBagResultFabricLength"><?php echo app_format_number($result['fabric_length_per_bag_m']); ?> m</td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_fabric_required'); ?></td>
                                        <td><?php echo app_format_number($result['fabric_required_meters']); ?> m</td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_fabric_rolls'); ?></td>
                                        <td><?php echo app_format_number($result['fabric_required_rolls']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_thread_total'); ?></td>
                                        <td><?php echo app_format_number($result['thread_total_meters']); ?> m</td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_thread_spools'); ?></td>
                                        <td><?php echo app_format_number($result['thread_spools_needed']); ?></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="panel_s">
                        <div class="panel-body">
                            <h4 class="_title"><?php echo _l('eco_bag_estimator_layout'); ?></h4>
                            <table class="table table-striped">
                                <tbody>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_panels_per_lay'); ?></td>
                                        <td><?php echo app_format_number($result['panels_per_lay']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_lay_count'); ?></td>
                                        <td><?php echo app_format_number($result['lay_count']); ?></td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_fabric_waste_percent'); ?></td>
                                        <td><?php echo app_format_number($result['fabric_waste_percent']); ?>%</td>
                                    </tr>
                                    <tr>
                                        <td><?php echo _l('eco_bag_estimator_fabric_waste_meters'); ?></td>
                                        <td><?php echo app_format_number($result['fabric_waste_meters']); ?> m</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="panel_s">
                <div class="panel-body">
                    <h4 class="_title"><?php echo _l('eco_bag_estimator_costs'); ?></h4>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo _l('eco_bag_estimator_cost_component'); ?></th>
                                    <th><?php echo _l('eco_bag_estimator_cost_amount'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo _l('eco_bag_estimator_fabric_cost_total'); ?></td>
                                    <td><?php echo app_format_money($result['fabric_cost_total'], get_base_currency()); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('eco_bag_estimator_thread_cost_total'); ?></td>
                                    <td><?php echo app_format_money($result['thread_cost_total'], get_base_currency()); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('eco_bag_estimator_labor_cost_total'); ?></td>
                                    <td><?php echo app_format_money($result['labor_cost_total'], get_base_currency()); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('eco_bag_estimator_waste_cost_total'); ?></td>
                                    <td><?php echo app_format_money($result['material_waste_cost'], get_base_currency()); ?></td>
                                </tr>
                                <tr>
                                    <td><?php echo _l('eco_bag_estimator_setup_costs'); ?></td>
                                    <td><?php echo app_format_money($result['setup_costs'], get_base_currency()); ?></td>
                                </tr>
                                <tr class="info">
                                    <td><?php echo _l('eco_bag_estimator_total_cost'); ?></td>
                                    <td><?php echo app_format_money($result['total_cost'], get_base_currency()); ?></td>
                                </tr>
                                <tr class="success">
                                    <td><?php echo _l('eco_bag_estimator_unit_cost'); ?></td>
                                    <td><?php echo app_format_money($result['unit_cost'], get_base_currency()); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="alert alert-info">
                <i class="fa fa-info-circle"></i> <?php echo _l('eco_bag_estimator_disclaimer'); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php init_tail(); ?>
