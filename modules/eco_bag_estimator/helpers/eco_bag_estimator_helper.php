<?php
defined('BASEPATH') or exit('No direct script access allowed');

if (!function_exists('eco_bag_estimator_option')) {
    function eco_bag_estimator_option(string $key, $default = null)
    {
        $value = get_option($key);
        return $value === false ? $default : $value;
    }
}

if (!function_exists('eco_bag_estimator_cm_to_m')) {
    function eco_bag_estimator_cm_to_m($centimeters)
    {
        return (float)$centimeters / 100;
    }
}

if (!function_exists('eco_bag_estimator_m_to_cm')) {
    function eco_bag_estimator_m_to_cm($meters)
    {
        return (float)$meters * 100;
    }
}

if (!function_exists('eco_bag_estimator_format_number')) {
    function eco_bag_estimator_format_number($number, int $decimals = 2)
    {
        return number_format((float)$number, $decimals, '.', ',');
    }
}

if (!function_exists('eco_bag_estimator_piece_area_m2')) {
    function eco_bag_estimator_piece_area_m2(array $piece)
    {
        $width = eco_bag_estimator_cm_to_m($piece['width_cm'] ?? 0);
        $height = eco_bag_estimator_cm_to_m($piece['height_cm'] ?? 0);
        $quantity = (int)($piece['quantity'] ?? 1);

        return $width * $height * $quantity;
    }
}

if (!function_exists('eco_bag_estimator_safe_float')) {
    function eco_bag_estimator_safe_float($value, $default = 0.0)
    {
        if ($value === null || $value === '') {
            return (float)$default;
        }
        return (float)$value;
    }
}
