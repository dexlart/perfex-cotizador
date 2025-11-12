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

if (!function_exists('eco_bag_estimator_products')) {
    function eco_bag_estimator_products(): array
    {
        $path = module_dir_path('eco_bag_estimator', 'config/products.php');

        if (!file_exists($path)) {
            return [];
        }

        return include $path;
    }
}

if (!function_exists('eco_bag_estimator_product')) {
    function eco_bag_estimator_product(?string $slug): ?array
    {
        if ($slug === null) {
            return null;
        }

        $products = eco_bag_estimator_products();

        return $products[$slug] ?? null;
    }
}

if (!function_exists('eco_bag_estimator_products_by_category')) {
    function eco_bag_estimator_products_by_category(): array
    {
        $products = eco_bag_estimator_products();
        $grouped = [];

        foreach ($products as $slug => $product) {
            $category = $product['category'] ?? (function_exists('_l') ? _l('eco_bag_estimator_category_uncategorized') : 'Sin categoría');

            if (!isset($grouped[$category])) {
                $grouped[$category] = [
                    'name' => $category,
                    'items' => [],
                ];
            }

            $grouped[$category]['items'][$slug] = $product;
        }

        ksort($grouped);

        return $grouped;
    }
}
