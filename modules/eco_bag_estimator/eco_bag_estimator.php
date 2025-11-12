<?php
defined('BASEPATH') or exit('No direct script access allowed');

register_activation_hook('eco_bag_estimator', 'eco_bag_estimator_install');
register_deactivation_hook('eco_bag_estimator', 'eco_bag_estimator_uninstall');
register_language_files('eco_bag_estimator', ['eco_bag_estimator']);

hooks()->add_action('admin_init', 'eco_bag_estimator_init_menu_items');
hooks()->add_action('admin_init', 'eco_bag_estimator_permissions');
hooks()->add_action('app_admin_head', 'eco_bag_estimator_add_head_components');
hooks()->add_action('app_admin_footer', 'eco_bag_estimator_add_footer_components');
hooks()->add_action('app_init', 'eco_bag_estimator_requirements');

function eco_bag_estimator_install()
{
    $CI = &get_instance();
    if (!$CI->db->table_exists(db_prefix() . 'eco_bag_estimator_presets')) {
        $table = db_prefix() . 'eco_bag_estimator_presets';
        $charset = $CI->db->char_set;
        $collation = $CI->db->dbcollat;
        $sql = "CREATE TABLE `{$table}` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `slug` VARCHAR(191) NOT NULL,
            `name` VARCHAR(191) NOT NULL,
            `specs` TEXT NOT NULL,
            `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET={$charset} COLLATE={$collation};";
        $CI->db->query($sql);
    }
}

function eco_bag_estimator_uninstall()
{
    $CI = &get_instance();
    if ($CI->db->table_exists(db_prefix() . 'eco_bag_estimator_presets')) {
        $CI->db->query('DROP TABLE `' . db_prefix() . 'eco_bag_estimator_presets`');
    }
}

function eco_bag_estimator_init_menu_items()
{
    $CI = &get_instance();

    if (!is_staff_logged_in()) {
        return;
    }

    if (!has_permission('eco_bag_estimator', '', 'view')) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('eco_bag_estimator', [
        'name'     => _l('eco_bag_estimator'),
        'href'     => admin_url('eco_bag_estimator'),
        'icon'     => 'fa fa-recycle',
        'position' => 45,
    ]);
}

function eco_bag_estimator_permissions()
{
    $capabilities = [];
    $capabilities['capabilities'] = [
        'view'   => _l('permission_view'),
        'create' => _l('permission_create'),
        'edit'   => _l('permission_edit'),
        'delete' => _l('permission_delete'),
    ];

    register_staff_capabilities('eco_bag_estimator', $capabilities, _l('eco_bag_estimator'));
}

function eco_bag_estimator_add_head_components()
{
    if (!function_exists('eco_bag_estimator_models')) {
        require_once module_dir_path('eco_bag_estimator', 'helpers/eco_bag_estimator_helper.php');
    }
    echo '<link rel="stylesheet" type="text/css" href="' . module_dir_url('eco_bag_estimator', 'assets/css/eco_bag_estimator.css') . '">';
}

function eco_bag_estimator_add_footer_components()
{
    if (!function_exists('eco_bag_estimator_models')) {
        require_once module_dir_path('eco_bag_estimator', 'helpers/eco_bag_estimator_helper.php');
    }
    $models = json_encode(eco_bag_estimator_models(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    echo '<script>window.app = window.app || {}; app.options = app.options || {}; app.options.ecoBagEstimatorModels = ' . $models . ';</script>';
    echo '<script src="' . module_dir_url('eco_bag_estimator', 'assets/js/eco_bag_estimator.js') . '"></script>';
}

function eco_bag_estimator_requirements()
{
    require_once module_dir_path('eco_bag_estimator', 'helpers/eco_bag_estimator_helper.php');
}

