<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('eco_bag_estimator_models')) {
    function eco_bag_estimator_models(): array
    {
        $path = module_dir_path('eco_bag_estimator', 'config/bag_models.php');

        if (!file_exists($path)) {
            return [];
        }

        return include $path;
    }
}

if (!function_exists('eco_bag_estimator_model')) {
    function eco_bag_estimator_model(string $slug): ?array
    {
        $models = eco_bag_estimator_models();

        return $models[$slug] ?? null;
    }
}

