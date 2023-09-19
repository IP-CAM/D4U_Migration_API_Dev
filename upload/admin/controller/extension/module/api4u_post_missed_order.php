<?php

require_once(DIR_SYSTEM . 'library/api4u/config.php');
require_once(API4U_LIBRARY . 'Curl/curl_include.php');
require_once(API4U_LIBRARY . 'APIExecution.php');
require_once(API4U_LIBRARY . 'common_functions.php');

use Curl\Curl;

class ControllerExtensionModuleApi4uPostMissedOrder extends Controller
{
    public $token = null;
    public $call = null;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('extension/module/api4u_log_request');
        $this->load->model('extension/module/api4u_order');
        $this->load->model('extension/module/api4u_customer');
        $this->call = new APIExecution($this->model_extension_module_api4u_log_request);
    }

    public function index(): void
    {
        $order_id = $this->request->post['order_id'];
        if (isset($this->call) && isset($order_id))
        {
            $error_file = 'error.log';
            //Error file
            $SQL = "SELECT `value`
                    FROM `" . DB_PREFIX . "setting`
                    WHERE `key` = 'config_error_filename';";
            $result = db_query_handler($this->db, $SQL);

            if ($result->num_rows)
            {
                $error_file = $result->row['value'];
            }
            define('API4U_ERROR_FILE', $error_file);

            $SQL = "SELECT `store_id`
                    FROM `" . DB_PREFIX . "order`
                    WHERE `order_id` = " . (int)$order_id . ";";
            $result = db_query_handler($this->db, $SQL);
            $store = $result->row['store_id'];
            $this->token = $this->getToken($store);

            $data_array = $this->prepareData($store);
            $token_bool = $this->checkToken($data_array['check_token']);
            if (!$token_bool)
            {
                $login_response = $this->login($data_array['login']);
                if (!empty($login_response))
                {
                    $this->token = $login_response['token'];
                    $this->storeToken($store, $login_response['created'], $login_response['expired']);
                }
                else
                {
                    log_error("[API4U] Fatal error:", 'Login response is empty.');
                    echo 'Cron process finished errors.';
                    exit();
                }
            }

            $data = array('order_id' => $order_id);
            $data_array = $this->prepareData($store);
            $get_order = $this->model_extension_module_api4u_order->index($store, 'get_missed_order', $data);
            if (empty($get_order[$order_id]))
            {
                log_error('[API4U] Notice:', 'Failed to send post order: ' . $order_id);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Order with id `$order_id` has already been sent to ERP."
                ));
                exit();
            }
            else
            {
                log_error('[API4U] Forced Notice Order:', $order_id);
            }
            $data_array['insert_orders']['data'] = "" . json_encode($get_order[$order_id], JSON_UNESCAPED_UNICODE) . "";
            $post_response = $this->postData($data_array['insert_orders']);

            if (empty($post_response))
            {
                log_error("[API4U] Fatal error:", 'Post order API response is empty.');
                echo json_encode(array(
                    "success" => true,
                    "message" => "Failed to post order with id `$order_id` to ERP."
                ));
                exit();
            }
            elseif (!isset($post_response['CustomerId']))
            {
                log_error("[API4U] Warning:", "{$post_response['Message']}: {$post_response['EntityCode']}");
                echo json_encode(array(
                    "success" => true,
                    "message" => "Failed to post order with id `$order_id` to ERP, {$post_response['Message']}: {$post_response['EntityCode']}"
                ));
                exit();
            }
            else
            {
                $data = array(
                    'customer_id' => $get_order['customer']['eshop_customer_id'],
                    'order_id' => $order_id, 'customer_erp_id' => $post_response['CustomerId'],
                    'api_custom_field' => json_encode(array('entity_id' => $post_response['EntityId'], 'entity_code' => $post_response['EntityCode']), JSON_UNESCAPED_UNICODE)
                );
                $this->model_extension_module_api4u_order->index($store, 'update_order', $data);
                if (!$get_order['customer']['erp_customer'])
                {
                    if ($get_order['customer']['eshop_customer_id'] != 0)
                    {
                        $this->model_extension_module_api4u_customer->index('update_customer_order', $data);
                    }
                    else
                    {
                        $this->model_extension_module_api4u_order->index('', 'update_guest_customer_order', $data);
                    }
                }

                $SQL = "UPDATE `" . DB_PREFIX . "session`
                        SET `data` = JSON_SET(`data`, '$.api_id', \"1\")
                        WHERE `session_id` = '" . $this->session->getId() . "';";
                db_query_handler($this->db, $SQL);

                $SQL = "INSERT INTO `" . DB_PREFIX . "api_session`(`api_id`, `session_id`, `ip`, `date_added`, `date_modified`)
                        VALUES (1, '" . $this->session->getId() . "', '{$_SERVER['REMOTE_ADDR']}', NOW(), NOW());";
                db_query_handler($this->db, $SQL);

                $url = NOTIFY_SHIPPED_ORDER_URL . "&api_token={$this->session->getId()}&store_id=$store&order_id=$order_id";
                $shipped_data = array(
                    'order_status_id' => 17,
                    'notify' => 1,
                    'override' => 0,
                    'append' => 0,
                    'comment' => '',
                    'content_type' => 1
                );

                $shipped_response = $this->postData($shipped_data, $url);
                if (!isset($shipped_response['success']))
                {
                    log_error("[API4U] Missed order shipped error:", json_encode($shipped_response));
                    echo json_encode(array(
                        "success" => false,
                        "message" => "Failed to send email to custumer for order with id `$order_id`"
                    ));
                    exit();
                }

                echo json_encode(array(
                    "success" => true,
                    "message" => "Order with id `$order_id` has been sent."
                ));
            }
        }
        else
        {
            log_error("[API4U] Fatal error:", 'Failed to initialize API execution process.');
            echo json_encode(array(
                "success" => false,
                "message" => "Process failed!"
            ));
            exit();
        }
    }

    public function login($data = array()): array
    {
        $this->call->url = ELOG_URL;
        return $this->call->login($data);
    }

    public function checkToken($data = null): bool
    {
        ## Check if token exists in token file ##

        $this->call->url = CHECK_TOKEN_URL;
        return $this->call->checkToken($data);
    }

    public function getData($data = null): array
    {
        $this->call->url = GET_DATA_URL;
        return $this->call->getData($data);
    }

    public function postData($data = null, $url = null): array
    {
        $this->call->url = POST_DATA_URL;
        if (isset($url))
        {
            $this->call->url = $url;
        }
        return $this->call->postData($data);
    }

    public function prepareData($store): array
    {
        $data_array = array();
        $api_code = !$store ? API_CODE_STORE_1 : API_CODE_STORE_2;
        $data_array['login'] = array(
            "apicode" => $api_code,
            "applicationname" => API_APP_NAME,
            "databasealias" => API_DB_ALIAS,
            "username" => API_DB_USERNAME,
            "password" => API_DB_PASSWORD
        );

        $data_array['check_token'] = array(
            "apicode" => $api_code,
            "cookie" => $this->token
        );

        $data_array['insert_orders'] = array(
            "cookie" => $this->token,
            "apicode" => $api_code,
            "entitycode" => "InsertOrders",
            "extras" => "{}",
        );

        return $data_array;
    }

    public function getToken($store): ?string
    {
        $token = null;
        $token_file = !$store ? TOKEN_FILE_STORE_1 : TOKEN_FILE_STORE_2;
        if (file_exists($token_file))
        {
            $token = file_get_contents($token_file);
        }
        return $token;
    }

    public function storeToken($store, $created, $expired): void
    {
        $store_token = false;
        $token_file = !$store ? TOKEN_FILE_STORE_1 : TOKEN_FILE_STORE_2;
        $time = (strtotime($expired) - strtotime($created)) + strtotime('now');
        $store_token = file_put_contents($token_file, $time . PHP_EOL . $this->token);
        if (!$store_token)
        {
            log_error("[API4U] Warning:", 'No token provided to store.');
        }
    }
}