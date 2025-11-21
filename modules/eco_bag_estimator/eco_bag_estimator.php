<?php
defined('BASEPATH') or exit('No direct script access allowed');
/*
Module Name: Eco Bag Estimator
Description: Estimador de tela, costos y precio para bolsas ecológicas; permite exportar a producto de Ventas.
Version: 1.1.0
Requires at least: 2.3.*
Author: Activación Web
Author URI: https://activacionweb.mx
*/

require_once __DIR__ . '/helpers/eco_bag_estimator_helper.php';

register_activation_hook('eco_bag_estimator', 'eco_bag_estimator_module_activation');
register_deactivation_hook('eco_bag_estimator', 'eco_bag_estimator_module_deactivation');
register_uninstall_hook('eco_bag_estimator', 'eco_bag_estimator_module_uninstall');

hooks()->add_action('admin_init', 'eco_bag_estimator_seed_presets_if_needed', 1);
hooks()->add_action('admin_init', 'eco_bag_estimator_register_menu');
hooks()->add_action('admin_init', 'eco_bag_estimator_register_permissions');
hooks()->add_action('app_admin_head', 'eco_bag_estimator_load_admin_head_assets');
hooks()->add_action('app_admin_footer', 'eco_bag_estimator_load_admin_footer_assets');

register_language_files('eco_bag_estimator', ['eco_bag_estimator']);

function eco_bag_estimator_module_activation()
{
    require_once __DIR__ . '/install.php';
    eco_bag_estimator_run_install();
}

function eco_bag_estimator_module_deactivation()
{
    // Reserved for future use. Perfex requires the callback to exist even if empty.
}

function eco_bag_estimator_module_uninstall()
{
    require_once __DIR__ . '/install.php';
    eco_bag_estimator_run_uninstall();
}

function eco_bag_estimator_seed_presets_if_needed()
{
    require_once __DIR__ . '/install.php';
    eco_bag_estimator_sync_seed_presets();
}

function eco_bag_estimator_register_menu()
{
    $CI = &get_instance();

    if (!is_staff_logged_in() || !has_permission('eco_bag_estimator', '', 'view')) {
        return;
    }

    $CI->app_menu->add_sidebar_menu_item('eco_bag_estimator', [
        'name'     => _l('eco_bag_estimator'),
        'href'     => admin_url('eco_bag_estimator'),
        'icon'     => 'fa fa-recycle',
        'position' => 45,
    ]);
}

function eco_bag_estimator_register_permissions()
{
    $capabilities = [
        'capabilities' => [
            'view'   => _l('permission_view'),
            'create' => _l('permission_create'),
            'edit'   => _l('permission_edit'),
            'delete' => _l('permission_delete'),
        ],
    ];

    register_staff_capabilities('eco_bag_estimator', $capabilities, _l('eco_bag_estimator'));
}

function eco_bag_estimator_load_admin_head_assets()
{
    if (defined('ECO_BAG_ESTIMATOR_ASSETS_HEAD')) {
        return;
    }

    define('ECO_BAG_ESTIMATOR_ASSETS_HEAD', true);

    echo '<link rel="stylesheet" href="' . eco_bag_estimator_asset_url('assets/css/eco_bag_estimator.css') . '">' . PHP_EOL;
}

function eco_bag_estimator_load_admin_footer_assets()
{
    if (defined('ECO_BAG_ESTIMATOR_ASSETS_FOOTER')) {
        return;
    }

    define('ECO_BAG_ESTIMATOR_ASSETS_FOOTER', true);

    echo '<script src="' . eco_bag_estimator_asset_url('assets/js/eco_bag_estimator.js') . '"></script>' . PHP_EOL;
}
