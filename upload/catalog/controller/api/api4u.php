<?php
class ControllerApiApi4u extends Controller {
    public function sync() {
        $this->load->language('api/api4u');

        $json = array();

        if (!isset($this->session->data['api_id'])) {
            $json['error'] = $this->language->get('error_permission');
        } else {
            ini_set('memory_limit', '1024M');

            require_once(DIR_SYSTEM . 'library/api4u/config.php');
            require(API4U_COMMON);

            require_once(API4U_INTEGRATION);
            require_once(API4U_LIBRARY . 'Curl/curl_include.php');
            require_once(API4U_LIBRARY . 'APIExecution.php');

			//Error file
			$error_file = 'error.log';
			usleep(rand(30000, 100000));
$SQL = "SELECT `value`
				FROM `" . DB_PREFIX . "setting`
				WHERE `key` = 'config_error_filename';";
			$result = db_query_handler($this->db, $SQL);
			if ($result->num_rows)
			{
				$error_file = $result->row['value'];
			}
			define('API4U_ERROR_FILE', $error_file);

            // Language
            usleep(rand(30000, 100000));
$SQL = "SELECT `language_id`
                FROM `" . DB_PREFIX . "language`;";
            $result = db_query_handler($this->db, $SQL);
            if ($result->num_rows)
            {
                $this->config->set('config_language_ids', array_map('set_languages', $result->rows));
            }

            // Customer group
            usleep(rand(30000, 100000));
$SQL = "SELECT `customer_group_id`
                FROM `" . DB_PREFIX . "customer_group`;";
            $result = db_query_handler($this->db, $SQL);
            if ($result->num_rows)
            {
                $this->config->set('config_customer_group_ids', array_map('set_customer_groups', $result->rows));
            }

            $api4u = new ControllerApi4uIntegration($this->registry);
            $api4u->index();
			$json['success'] = $this->language->get('text_success');
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
}
