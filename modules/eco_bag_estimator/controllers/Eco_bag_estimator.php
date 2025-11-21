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

        $data = [
            'title'            => _l('eco_bag_estimator'),
            'presets'          => $this->eco_bag_estimator_model->get_presets(),
            'options'          => $this->eco_bag_estimator_model->get_configuration_options(),
            'can_edit_options' => has_permission('eco_bag_estimator', '', 'edit'),
        ];

        $this->load->view(module_views_path('eco_bag_estimator', 'admin/index'), $data);
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

}
