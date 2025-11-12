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
        $result = null;
        $formData = $this->input->post() ?: [];

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
            'result' => $result,
            'formData' => $formData,
        ];

        $this->load->view('eco_bag_estimator/index', $data);
    }
}
