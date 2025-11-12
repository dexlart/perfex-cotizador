<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="_title"><?php echo _l('eco_bag_estimator_title'); ?></h4>
                        <p class="text-muted"><?php echo _l('eco_bag_estimator_description'); ?></p>
                        <?php echo form_open(admin_url('eco_bag_estimator'), ['method' => 'post']); ?>
                        <div class="row">
                            <div class="col-md-4">
                                <?php echo render_select('model', array_map(function ($slug, $config) {
                                    return ['id' => $slug, 'name' => $config['name']];
                                }, array_keys($models), $models), ['id', 'name'], _l('eco_bag_estimator_select_model'), $formData['model'] ?? ''); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('quantity', _l('eco_bag_estimator_quantity'), $formData['quantity'] ?? 1000, 'number', ['min' => 1]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('fabric_roll_width_cm', _l('eco_bag_estimator_fabric_roll_width'), $formData['fabric_roll_width_cm'] ?? 160, 'number', ['step' => '0.01', 'min' => 1]); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?php echo render_input('fabric_roll_length_m', _l('eco_bag_estimator_fabric_roll_length'), $formData['fabric_roll_length_m'] ?? 500, 'number', ['step' => '0.01', 'min' => 1]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('fabric_cost_per_meter', _l('eco_bag_estimator_fabric_cost'), $formData['fabric_cost_per_meter'] ?? '', 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('labor_cost_per_bag', _l('eco_bag_estimator_labor_cost'), $formData['labor_cost_per_bag'] ?? '', 'number', ['step' => '0.0001', 'min' => 0]); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?php echo render_input('thread_per_bag_m', _l('eco_bag_estimator_thread_per_bag'), $formData['thread_per_bag_m'] ?? '', 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('thread_spool_length_m', _l('eco_bag_estimator_thread_spool_length'), $formData['thread_spool_length_m'] ?? '', 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                            <div class="col-md-4">
                                <?php echo render_input('thread_spool_cost', _l('eco_bag_estimator_thread_spool_cost'), $formData['thread_spool_cost'] ?? '', 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <?php echo render_input('additional_costs', _l('eco_bag_estimator_additional_costs'), $formData['additional_costs'] ?? 0, 'number', ['step' => '0.01', 'min' => 0]); ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 text-right">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-calculator"></i> <?php echo _l('eco_bag_estimator_calculate'); ?>
                                </button>
                            </div>
                        </div>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
        <?php if (!empty($result)) : ?>
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="_title"><?php echo _l('eco_bag_estimator_results_title'); ?></h4>
                        <div class="row">
                            <div class="col-md-6">
                                <h5><?php echo _l('eco_bag_estimator_materials'); ?></h5>
                                <table class="table table-striped">
                                    <tbody>
                                        <tr>
                                            <td><?php echo _l('eco_bag_estimator_fabric_width_usage'); ?></td>
                                            <td><?php echo app_format_number($result['fabric_width_usage_cm']); ?> cm</td>
                                        </tr>
                                        <tr>
                                            <td><?php echo _l('eco_bag_estimator_fabric_length_per_bag'); ?></td>
                                            <td><?php echo app_format_number($result['fabric_length_per_bag_m']); ?> m</td>
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
                            <div class="col-md-6">
                                <h5><?php echo _l('eco_bag_estimator_layout'); ?></h5>
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
                        <div class="row">
                            <div class="col-md-12">
                                <h5><?php echo _l('eco_bag_estimator_costs'); ?></h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
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
                            <p><?php echo _l('eco_bag_estimator_disclaimer'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php init_tail(); ?>
