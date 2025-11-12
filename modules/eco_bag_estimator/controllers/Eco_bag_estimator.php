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

        $data['title']             = _l('eco_bag_estimator');
        $data['presets']           = $this->eco_bag_estimator_model->get_presets();
        $data['options']           = $this->eco_bag_estimator_model->get_configuration_options();
        $data['can_edit_options']  = has_permission('eco_bag_estimator', '', 'edit');

        $viewPath = module_views_path('eco_bag_estimator', 'admin/index');
        $this->load->view($this->normalize_module_view_path($viewPath), $data);
    }

    public function calculate()
    {
        if (!has_permission('eco_bag_estimator', '', 'view') && !has_permission('eco_bag_estimator', '', 'create')) {
            access_denied('eco_bag_estimator');
        }

        $this->output->set_content_type('application/json');

        try {
            $payload = $this->input->post(null, true);
            $result  = $this->eco_bag_estimator_model->calculate($payload);

            $this->output->set_output(json_encode([
                'status' => true,
                'data'   => $result,
            ]));
        } catch (Exception $exception) {
            log_message('error', 'Eco Bag Estimator calculate error: ' . $exception->getMessage());
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode([
                'status'  => false,
                'message' => $exception->getMessage(),
            ]));
        }
    }

    public function update_options()
    {
        if (!has_permission('eco_bag_estimator', '', 'edit')) {
            access_denied('eco_bag_estimator');
        }

        $this->output->set_content_type('application/json');

        try {
            $payload = $this->input->post(null, true);
            $options = $this->eco_bag_estimator_model->save_configuration_options($payload);

            $this->output->set_output(json_encode([
                'status'  => true,
                'message' => _l('eco_bag_estimator_options_saved'),
                'data'    => $options,
            ]));
        } catch (Exception $exception) {
            log_message('error', 'Eco Bag Estimator update options error: ' . $exception->getMessage());
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode([
                'status'  => false,
                'message' => $exception->getMessage(),
            ]));
        }
    }

    public function save_as_product()
    {
        if (!has_permission('eco_bag_estimator', '', 'create')) {
            access_denied('eco_bag_estimator');
        }

        $this->output->set_content_type('application/json');

        try {
            $payload   = $this->input->post(null, true);
            $productId = $this->eco_bag_estimator_model->save_as_product($payload);

            $this->output->set_output(json_encode([
                'status'  => true,
                'message' => _l('eco_bag_estimator_saved_product'),
                'id'      => $productId,
            ]));
        } catch (Exception $exception) {
            log_message('error', 'Eco Bag Estimator save product error: ' . $exception->getMessage());
            $this->output->set_status_header(400);
            $this->output->set_output(json_encode([
                'status'  => false,
                'message' => $exception->getMessage(),
            ]));
        }
    }

    /**
     * Perfex installations placed in subdirectories can resolve module_views_path
     * to an absolute filesystem path. This helper converts any absolute
     * reference into the relative identifier that CI's loader expects while
     * keeping plain identifiers untouched.
     */
    private function normalize_module_view_path($viewPath)
    {
        $normalized = str_replace('\\', '/', $viewPath);

        $prefixes = [];

        if (defined('FCPATH')) {
            $prefixes[] = rtrim(FCPATH, '/') . '/modules/eco_bag_estimator/views/';
        }

        if (defined('APPPATH')) {
            $prefixes[] = APPPATH . 'modules/eco_bag_estimator/views/';
        }

        $prefixes[] = 'modules/eco_bag_estimator/views/';

        foreach ($prefixes as $prefix) {
            if (strpos($normalized, $prefix) === 0) {
                $normalized = substr($normalized, strlen($prefix));
                break;
            }
        }

        $normalized = ltrim($normalized, '/');

        if ($normalized === '' || $normalized === $viewPath) {
            return $viewPath;
        }

        if (substr($normalized, -4) === '.php') {
            $normalized = substr($normalized, 0, -4);
        }

        return 'eco_bag_estimator/' . $normalized;
    }
}
