<?php
defined('BASEPATH') or exit('No direct script access allowed');

function eco_bag_estimator_run_install()
{
    $CI = &get_instance();

    $table = db_prefix() . 'eco_bag_estimator_presets';

    if (!$CI->db->table_exists($table)) {
        $CI->db->query('CREATE TABLE `' . $table . "` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `model` VARCHAR(64) NOT NULL,
            `name` VARCHAR(191) NOT NULL,
            `specs_json` TEXT NOT NULL,
            `hidden_costs_json` TEXT NULL,
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_model` (`model`)
        ) ENGINE=InnoDB DEFAULT CHARSET=" . $CI->db->char_set . ' COLLATE=' . $CI->db->dbcollat . ';');
    }

    $defaults = [
        'eco_bag_price_per_meter'             => '7.00',
        'eco_bag_stitch_cost_with_gusset'     => '3.00',
        'eco_bag_stitch_cost_drawstring'      => '4.00',
        'eco_bag_stitch_cost_no_gusset'       => '2.00',
        'eco_bag_electricity_per_bag'         => '0.12',
        'eco_bag_misc_percentage'             => '3',
        'eco_bag_general_waste_percentage'    => '5',
        'eco_bag_fabric_width_m'              => '1.60',
        'eco_bag_layout_length_min_cm'        => '100',
        'eco_bag_layout_length_max_cm'        => '200',
        'eco_bag_layout_waste_percentage'     => '0',
        'eco_bag_handle_width_cm'             => '9',
    ];

    foreach ($defaults as $key => $value) {
        if (false === get_option($key)) {
            add_option($key, $value);
        }
    }

    $preset = [
        'model'            => 'BCF-301',
        'name'             => 'Bolsa con fuelle chica',
        'specs_json'       => json_encode([
            'bag_type' => 'with_gusset',
            'base_cm'  => 30,
            'height_cm'=> 36,
            'gusset_cm'=> 12,
            'handle_cm'=> 50,
            'pieces'   => [
                ['label' => 'Cuerpo envuelto', 'width_cm' => 32, 'height_cm' => 92, 'quantity' => 1],
                ['label' => 'Asa', 'width_cm' => 50, 'height_cm' => 9, 'quantity' => 2],
                ['label' => 'Fuelle', 'width_cm' => 12, 'height_cm' => 40, 'quantity' => 2],
            ],
        ], JSON_UNESCAPED_UNICODE),
        'hidden_costs_json' => json_encode([
            'layout_waste_percentage' => (float)get_option('eco_bag_layout_waste_percentage'),
        ]),
        'created_at'       => date('Y-m-d H:i:s'),
    ];

    $exists = $CI->db->where('model', $preset['model'])->get($table)->row();
    if (!$exists) {
        $CI->db->insert($table, $preset);
    }
}

function eco_bag_estimator_run_uninstall()
{
    $CI = &get_instance();
    $table = db_prefix() . 'eco_bag_estimator_presets';

    if ($CI->db->table_exists($table)) {
        $CI->db->query('DROP TABLE `' . $table . '`');
    }
}
