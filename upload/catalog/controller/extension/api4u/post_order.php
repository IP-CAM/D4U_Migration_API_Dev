<?php

require_once(DIR_SYSTEM . 'library/api4u/config.php');
require_once(API4U_LIBRARY . 'Curl/curl_include.php');
require_once(API4U_LIBRARY . 'APIExecution.php');
require_once(API4U_LIBRARY . 'common_functions.php');

use Curl\Curl;

class ControllerApi4uPostOrder extends Controller
{
    public $token = null;
    public $call = null;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('extension/api4u/log_request');
        $this->load->model('extension/api4u/order');
        $this->load->model('extension/api4u/customer');
        $this->call = new APIExecution($this->model_extension_api4u_log_request);
    }

    public function index($data, $order_id)
    {
        if (isset($this->call))
        {
            $error_file = 'error.log';
            //Error file
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

            if (isset($_SESSION["post_order"]))
            {
                return;
            }
            $_SESSION["post_order"] = true;

            usleep(rand(30000, 100000));
$SQL = "UPDATE `" . DB_PREFIX . "session`
                    SET `data` = REPLACE(`data`, 'order_id', '')
                    WHERE data LIKE '%\"order_id\":" . $this->session->data['order_id'] . "%';";
            db_query_handler($this->db, $SQL);

            $this->token = $this->getToken();

            $data_array = $this->prepareData();
            $token_bool = $this->checkToken($data_array['check_token']);
            if (!$token_bool)
            {
                $login_response = $this->login($data_array['login']);
                if (!empty($login_response))
                {
                    $this->token = $login_response['token'];
                    $this->storeToken($login_response['created'], $login_response['expired']);
                }
                else
                {
                    log_error("[API4U] Fatal error:", 'Login response is empty.');
                    echo 'Cron process finished errors.';
                    exit();
                }
            }

            $data = array('order_id' => $order_id);
            $data_array = $this->prepareData();
            $get_order = $this->model_extension_api4u_order->index('get_order', $data);
            if (empty($get_order[$order_id]))
            {
                log_error('[API4U] Notice:', 'Tried to send post orders.');
                return;
            }
            else{
                log_error('[API4U] Notice Order:', $order_id);
            }
            $data_array['insert_orders']['data'] = "" . json_encode($get_order[$order_id], JSON_UNESCAPED_UNICODE) . "";
            $post_response = $this->postData($data_array['insert_orders']);

            if (empty($post_response))
            {
                log_error("[API4U] Fatal error:", 'Post order API response is empty.');
            }
            elseif (!isset($post_response['CustomerId']))
            {
                log_error("[API4U] Warning:", "{$post_response['Message']}: {$post_response['EntityCode']}");
            }
            else
            {
                $data = array(
                    'customer_id' => $get_order['customer']['eshop_customer_id'],
                    'order_id' => $order_id, 'customer_erp_id' => $post_response['CustomerId'],
                    'api_custom_field' => json_encode(array('entity_id' => $post_response['EntityId'], 'entity_code' => $post_response['EntityCode']), JSON_UNESCAPED_UNICODE)
                );
                $this->model_extension_api4u_order->index('', 'update_order', $data);
                if (!$get_order['customer']['erp_customer'])
                {
                    if ($get_order['customer']['eshop_customer_id'] != 0)
                    {
                        $this->model_extension_api4u_customer->index('update_customer_order', $data);
                    }
                    else
                    {
                        $this->model_extension_api4u_order->index('', 'update_guest_customer_order', $data);
                    }
                }
                unset ($_SESSION["post_order"]);
            }
        }
        else
        {
            log_error("[API4U] Fatal error:", 'Failed to initialize API execution process.');
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

    public function postData($data = null): array
    {
        $this->call->url = POST_DATA_URL;
        return $this->call->postData($data);
    }

    public function prepareData(): array
    {
        $data_array = array();
        $data_array['login'] = array(
            "apicode" => API_CODE,
            "applicationname" => API_APP_NAME,
            "databasealias" => API_DB_ALIAS,
            "username" => API_DB_USERNAME,
            "password" => API_DB_PASSWORD
        );

        $data_array['check_token'] = array(
            "apicode" => API_CODE,
            "cookie" => $this->token
        );

        $data_array['insert_orders'] = array(
            "cookie" => $this->token,
            "apicode" => API_CODE,
            "entitycode" => "InsertOrders",
            "extras" => "{}",
        );

        return $data_array;
    }

    public function getToken(): ?string
    {
        $token = null;
        if (file_exists(TOKEN_FILE))
        {
            $token = file_get_contents(TOKEN_FILE);
        }
        return $token;
    }

    public function storeToken($created, $expired): void
    {
        $store_token = false;
        $time = (strtotime($expired) - strtotime($created)) + strtotime('now');
        $store_token = file_put_contents(TOKEN_FILE, $time . PHP_EOL . $this->token);
        if (!$store_token)
        {
            log_error("[API4U] Warning:", 'No token provided to store.');
        }
    }
}