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

    eco_bag_estimator_sync_seed_presets();
}

function eco_bag_estimator_run_uninstall()
{
    $CI = &get_instance();
    $table = db_prefix() . 'eco_bag_estimator_presets';

    if ($CI->db->table_exists($table)) {
        $CI->db->query('DROP TABLE `' . $table . '`');
    }
}

if (!function_exists('eco_bag_estimator_sync_seed_presets')) {
    function eco_bag_estimator_sync_seed_presets()
    {
        $CI = &get_instance();
        if (!$CI) {
            return;
        }

        $table = db_prefix() . 'eco_bag_estimator_presets';
        if (!$CI->db->table_exists($table)) {
            return;
        }

        $seedMetadata = [
            'layout_waste_percentage' => (float)get_option('eco_bag_layout_waste_percentage'),
            'seed'                    => true,
        ];

        $presets = eco_bag_estimator_seed_presets_data();

        // Remove legacy default model if present
        $CI->db->where('model', 'BCF-301')->delete($table);

        foreach ($presets as $preset) {
            $data = [
                'name'             => $preset['name'],
                'specs_json'       => json_encode($preset['specs'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'hidden_costs_json'=> json_encode($seedMetadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];

            $existing = $CI->db->where('model', $preset['model'])->get($table)->row();
            if ($existing) {
                $CI->db->where('id', $existing->id)->update($table, $data);
            } else {
                $data['model'] = $preset['model'];
                $data['created_at'] = date('Y-m-d H:i:s');
                $CI->db->insert($table, $data);
            }
        }
    }
}

if (!function_exists('eco_bag_estimator_seed_presets_data')) {
    function eco_bag_estimator_seed_presets_data(): array
    {
        $estimateHandle = static function (float $base) {
            $base = max(0, $base);
            $length = ($base * 2) + 20;
            return round(max($length, 30), 2);
        };

        return [
            [
                'model' => 'BPM-1101',
                'name'  => 'Bolsa Plana de Manta con Asas Base 25cm, Altura 30cm, Asas 45cm',
                'specs' => [
                    'bag_type'  => 'without_gusset',
                    'base_cm'   => 25,
                    'height_cm' => 30,
                    'gusset_cm' => 0,
                    'handle_cm' => 45,
                ],
            ],
            [
                'model' => 'BPM-1102',
                'name'  => 'Bolsa Plana de Manta con Asas Base 32cm, Altura 25cm, Asas 45cm',
                'specs' => [
                    'bag_type'  => 'without_gusset',
                    'base_cm'   => 32,
                    'height_cm' => 25,
                    'gusset_cm' => 0,
                    'handle_cm' => 45,
                ],
            ],
            [
                'model' => 'BPM-1103',
                'name'  => 'Bolsa Plana de Manta con Asas Base 33cm, Altura 40cm, Asas 50cm',
                'specs' => [
                    'bag_type'  => 'without_gusset',
                    'base_cm'   => 33,
                    'height_cm' => 40,
                    'gusset_cm' => 0,
                    'handle_cm' => 50,
                ],
            ],
            [
                'model' => 'BPM-1104',
                'name'  => 'Bolsa Plana de Manta con Asas Base 40cm, Altura 45cm, Asas 60cm',
                'specs' => [
                    'bag_type'  => 'without_gusset',
                    'base_cm'   => 40,
                    'height_cm' => 45,
                    'gusset_cm' => 0,
                    'handle_cm' => 60,
                ],
            ],
            [
                'model' => 'BPM-1105',
                'name'  => 'Bolsa Plana de Manta con Asas Base 45cm, Altura 35cm, Asas 55cm',
                'specs' => [
                    'bag_type'  => 'without_gusset',
                    'base_cm'   => 45,
                    'height_cm' => 35,
                    'gusset_cm' => 0,
                    'handle_cm' => 55,
                ],
            ],
            [
                'model' => 'BFSM-1201',
                'name'  => 'Bolsa Plana de Manta con Fuelle Simulado Inferior y Asas, Base 33cm, Altura 24cm, Fuelle 8cm, Asas 45cm',
                'specs' => [
                    'bag_type'  => 'with_gusset',
                    'base_cm'   => 33,
                    'height_cm' => 24,
                    'gusset_cm' => 8,
                    'handle_cm' => 45,
                ],
            ],
            [
                'model' => 'BFSM-1202',
                'name'  => 'Bolsa Plana de Manta con Fuelle Simulado Inferior y Asas, Base 33cm, Altura 38cm, Fuelle 10cm, Asas 50cm',
                'specs' => [
                    'bag_type'  => 'with_gusset',
                    'base_cm'   => 33,
                    'height_cm' => 38,
                    'gusset_cm' => 10,
                    'handle_cm' => 50,
                ],
            ],
            [
                'model' => 'BFSM-1203',
                'name'  => 'Bolsa Plana de Manta con Fuelle Simulado Inferior y Asas, Base 42cm, Altura 35cm, Fuelle 12cm, Asas 60cm',
                'specs' => [
                    'bag_type'  => 'with_gusset',
                    'base_cm'   => 42,
                    'height_cm' => 35,
                    'gusset_cm' => 12,
                    'handle_cm' => 60,
                ],
            ],
            [
                'model' => 'BFSM-1204',
                'name'  => 'Bolsa Plana de Manta con Fuelle Simulado Inferior y Asas, Base 42cm, Altura 45cm, Fuelle 12cm, Asas 60cm',
                'specs' => [
                    'bag_type'  => 'with_gusset',
                    'base_cm'   => 42,
                    'height_cm' => 45,
                    'gusset_cm' => 12,
                    'handle_cm' => 60,
                ],
            ],
            [
                'model' => 'BFM-1301',
                'name'  => 'Bolsa de Manta con Fuelle, Bies Perimetral y Asas, Base 32cm, Altura 38cm, Fuelle 12cm, Asas 50cm',
                'specs' => [
                    'bag_type'  => 'with_gusset',
                    'base_cm'   => 32,
                    'height_cm' => 38,
                    'gusset_cm' => 12,
                    'handle_cm' => 50,
                ],
            ],
            [
                'model' => 'BFM-1302',
                'name'  => 'Bolsa de Manta con Fuelle, Bies Perimetral y Asas Base, 40cm, Altura 45cm, Fuelle 15cm, Asas 60cm',
                'specs' => [
                    'bag_type'  => 'with_gusset',
                    'base_cm'   => 40,
                    'height_cm' => 45,
                    'gusset_cm' => 15,
                    'handle_cm' => 60,
                ],
            ],
            [
                'model' => 'CJM-1401',
                'name'  => 'Costal de Manta con Jareta de Algodón, Base 33cm, Altura 40cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 33,
                    'height_cm' => 40,
                    'gusset_cm' => 0,
                    'handle_cm' => $estimateHandle(33),
                ],
            ],
            [
                'model' => 'CDJM-1451',
                'name'  => 'Costal de Manta con Doble Jareta de Algodón, Base 9cm, Altura 14cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 9,
                    'height_cm' => 14,
                    'gusset_cm' => 0,
                    'handle_cm' => $estimateHandle(9),
                ],
            ],
            [
                'model' => 'CDJM-1452',
                'name'  => 'Costal de Manta con Doble Jareta de Algodón, Base 14cm, Altura 20cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 14,
                    'height_cm' => 20,
                    'gusset_cm' => 0,
                    'handle_cm' => $estimateHandle(14),
                ],
            ],
            [
                'model' => 'CDJM-1453',
                'name'  => 'Costal de Manta con Doble Jareta de Algodón, Base 16cm, Altura 25cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 16,
                    'height_cm' => 25,
                    'gusset_cm' => 0,
                    'handle_cm' => $estimateHandle(16),
                ],
            ],
            [
                'model' => 'CDJM-1454',
                'name'  => 'Costal de Manta con Doble Jareta de Algodón, Base 18cm, Altura 30cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 18,
                    'height_cm' => 30,
                    'gusset_cm' => 0,
                    'handle_cm' => $estimateHandle(18),
                ],
            ],
            [
                'model' => 'CDJM-1455',
                'name'  => 'Costal de Manta con Doble Jareta de Algodón, Base 23cm, Altura 32cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 23,
                    'height_cm' => 32,
                    'gusset_cm' => 0,
                    'handle_cm' => $estimateHandle(23),
                ],
            ],
            [
                'model' => 'MM-1501',
                'name'  => 'Morral de Manta con Jaretas de Algodón y Ojillos de Metal, Base 33cm Altura 40cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 33,
                    'height_cm' => 40,
                    'gusset_cm' => 0,
                    'handle_cm' => $estimateHandle(33),
                ],
            ],
            [
                'model' => 'BBM-1801',
                'name'  => 'Bolsa de Manta con Base y Doble Jareta, Base 8x8cm, Altura 36cm',
                'specs' => [
                    'bag_type'  => 'drawstring',
                    'base_cm'   => 8,
                    'height_cm' => 36,
                    'gusset_cm' => 8,
                    'handle_cm' => $estimateHandle(8),
                ],
            ],
        ];
    }
}
