<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Eco_bag_estimator extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model('eco_bag_estimator_model');
        $this->load->helper('eco_bag_estimator/eco_bag_estimator');
    }

    public function index()
    {
        if (!has_permission('eco_bag_estimator', '', 'view')) {
            access_denied('eco_bag_estimator');
        }

        $models = eco_bag_estimator_models();
        $productsFlat = eco_bag_estimator_products();
        $productsByCategory = eco_bag_estimator_products_by_category();
        $result = null;
        $formData = $this->input->post() ?: [];
        $selectedProductSlug = $formData['product_slug'] ?? null;
        $selectedProduct = $selectedProductSlug && isset($productsFlat[$selectedProductSlug])
            ? array_merge(['slug' => $selectedProductSlug], $productsFlat[$selectedProductSlug])
            : null;

        if (!$selectedProduct && !empty($productsFlat)) {
            $firstSlug = array_key_first($productsFlat);
            $selectedProduct = array_merge(['slug' => $firstSlug], $productsFlat[$firstSlug]);
            $selectedProductSlug = $firstSlug;

            if (empty($formData)) {
                $formData['product_slug'] = $firstSlug;
            }
        }

        if ($this->input->method() === 'post') {
            try {
                $result = $this->eco_bag_estimator_model->calculate($this->input->post());
                set_alert('success', _l('eco_bag_estimator_calculated_successfully'));
            } catch (Throwable $exception) {
                set_alert('danger', $exception->getMessage());
            }
        }

        $data = [
            'title' => _l('eco_bag_estimator'),
            'models' => $models,
            'productsByCategory' => $productsByCategory,
            'productsFlat' => $productsFlat,
            'selectedProduct' => $selectedProduct,
            'result' => $result,
            'formData' => $formData,
        ];

        $this->load->view('eco_bag_estimator/admin/index', $data);
    }
}
