<?php
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);
class ControllerExtensionModuleApi4uIntegration extends Controller
{
    private $error = array();
    public $token = null;
    public $package_number = 1;
    public $call = null;
    public $data_array = array();
    public $active_integration = false;

    public function install()
    {
        define('API4U_ERROR_FILE', 'error.log');
        require(DIR_SYSTEM . 'library/api4u/common_functions.php');
        $this->load->model('extension/module/api4u_migrations');
        $this->load->model('setting/setting');
        $this->model_extension_module_api4u_migrations->up();
        $this->model_setting_setting->editSetting('module_api4u_integration', array('module_api4u_integration_status' => 1));
        $this->session->data['success'] = $this->language->get('text_success');
    }

    public function uninstall()
    {
        define('API4U_ERROR_FILE', 'error.log');
        require(DIR_SYSTEM . 'library/api4u/common_functions.php');
        $this->load->model('extension/module/api4u_migrations');
        $this->load->model('setting/setting');
        $this->model_extension_module_api4u_migrations->down();
        $this->model_setting_setting->deleteSetting('module_api4u_integration');
    }

    public function index()
    {
        if ($this->request->server['REQUEST_METHOD'] == 'GET')
        {
            $this->api4uIntegrationModule();
        }
        else
        {
            header("HTTP/1.1 405 Method Not Allowed");
        }
    }

    public function api4uIntegrationModule()
    {
        $this->load->language('extension/module/api4u_integration');

        $this->document->setTitle($this->language->get('heading_title'));

        $this->document->addStyle('view/stylesheet/api4u/integration.css');

        $this->document->addScript('view/javascript/api4u/integration.js');

        $data['user_token'] = $this->session->data['user_token'];

        $data['heading_title'] = $this->language->get('heading_title');

        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');


        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning']))
        {
            $data['error_warning'] = $this->error['warning'];
        }
        else
        {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extension'),
            'href' => $this->url->link('extension/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/api4u_integration', 'user_token=' . $this->session->data['user_token'], true)
        );

        $data['action'] = $this->url->link('extension/module/api4u_integration', 'user_token=' . $this->session->data['user_token'], true);

        if (isset($this->request->post['integration_status']))
        {
            $data['integration_status'] = $this->request->post['integration_status'];
        }
        else
        {
            $data['integration_status'] = $this->config->get('integration_status');
        }

        // API login
		$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;
		
		// API login
		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		
			$session = new Session($this->config->get('session_engine'), $this->registry);
			
			$session->start();
					
			$this->model_user_api->deleteApiSessionBySessionId($session->getId());
			
			$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
			
			$session->data['api_id'] = $api_info['api_id'];

			$data['api_token'] = $session->getId();


        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/api4u_integration', $data));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'extension/module/api4u_integration'))
        {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error;
    }
}