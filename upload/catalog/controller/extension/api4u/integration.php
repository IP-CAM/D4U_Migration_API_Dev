<?php
ini_set('memory_limit', '1024M');
if (PHP_SAPI != 'cli' && !isset($this->session->data['api_id'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

class ControllerApi4uIntegration extends Controller
{
    public $token = null;
    public $package_number = 1;
    public $call = null;
    public $data_array = array();
    public $active_integration = false;
    public $clothing_size_order = array();
    public $active_api_ids = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('extension/api4u/log_request');
        $this->load->model('extension/api4u/category');
        $this->load->model('extension/api4u/attribute');
        $this->load->model('extension/api4u/filter');
        $this->load->model('extension/api4u/option');
        $this->load->model('extension/api4u/product');
        $this->load->model('extension/api4u/related_option');
        $this->load->model('extension/api4u/export_excel');
        $this->load->model('extension/api4u/order');
        $this->load->model('extension/api4u/customer');
        $this->load->model('extension/api4u/manufacturer');
        $this->call = new APIExecution($this->model_extension_api4u_log_request);
        $this->token = $this->getToken();
    }

    public function index(): void
    {
        if (isset($this->call)) {
            $this->syncStore();
            echo 'Cron process finished successfully';
        } else {
            log_error("[API4U] Fatal error:", 'Failed to initialize API execution process.');
            echo 'Cron process finished with errors.';
            $this->db->close();
            exit();
        }
    }

    public function integrationProcess(string $process) {
        if(stripos($process, 'prod') !== false) { $this->integrateProducts(); }
        if(stripos($process, 'relat') !== false) { $this->integrateRelatedOptions(); }
        if(stripos($process, 'categor') !== false) { $this->integrateCategories(); }
        if(stripos($process, 'attrib') !== false) { $this->integrateAttributeGroups(); }
        if(stripos($process, 'group') !== false) { $this->integrateFilterGroups(); }
        if(stripos($process, 'filte') !== false) { $this->integrateFilters(); }
        if(stripos($process, 'option') !== false) { $this->integrateOptions(); }
        if(stripos($process, 'value') !== false) { $this->integrateOptionValues(); }
        if(stripos($process, 'manuf') !== false) { $this->integrateManufacturers(); }
        if(stripos($process, 'descr') !== false) { $this->integrateProductsDescription(); }

        if(stripos($process, 'active') !== false || stripos($process, 'shop') !== false) { $this->updateEshopActiveProducts($store); }
        if(stripos($process, 'order') !== false) { $this->postOrders($store); }
        if(stripos($process, 'ship') !== false) { $this->shippedOrders($store); }
        if(stripos($process, 'excel') !== false || stripos($process, 'export') !== false) { $this->exportExcel($store); }

        $this->active_api_ids = array();
    }

    public function syncStore($store = 0): void
    {
        $this->active_integration = false;
        $this->data_array = $this->prepareData($store);

        if(NEEDS_LOGIN_TOKEN == true) {
            $token_bool = $this->checkToken($this->data_array['check_token']);
            if (!$token_bool) {
                $login_response = $this->login($this->data_array['login']);
                if (!empty($login_response)) {
                    $this->token = $login_response['token'];
                    $this->data_array = $this->prepareData($store);
                    $this->storeToken($login_response['created'], $login_response['expired']);
                } else {
                    log_error("[API4U] Fatal error:", 'Login response is empty.');
                    echo 'Cron process finished with errors.';
                    exit();
                }
            }
        } else {
            $login_response = $this->login($this->data_array['login']);   
            if (!empty($login_response)) {
                $this->token = $login_response['token'];
                $this->data_array = $this->prepareData($store);
                $this->storeToken($login_response['created'], $login_response['expired']);
            } else {
                log_error("[API4U] Fatal error:", 'Login response is empty.');
                echo 'Cron process finished with errors.';
                exit();
            }
        }

        $integrate_process = INTEGRATE_PROCESS;
        if(is_array($integrate_process)) {
            if(!empty($integrate_process[1])) {
                foreach($integrate_process as $process) {
                    $this->integrationProcess($process);
                }
            } else {
                $process = $integrate_process[0];
                $this->integrationProcess($process);
            }
        } else {
            log_error("[API4U] Error:", 'Please fill the $integrate_process at "confing.php", to know which process to run.');
            return;
        }
    }

    //Start ERP procedures <<
    public function login($data = array()): array
    {
        $this->call->url = ELOG_URL;
        return $this->call->login($data);
    }

    public function checkToken($data = null): bool
    {
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

    public function prepareData($store): array
    {
        $api_code = API_CODE_0;
        $data_array = array();
        $data_array['login'] = array(
            "apicode" => $api_code,
            "applicationname" => API_APP_NAME,
            "databasealias" => API_DB_ALIAS,
            "username" => API_DB_USERNAME,
            "password" => API_DB_PASSWORD,
        );

        $data_array['check_token'] = array(
            "apicode" => $api_code,
            "cookie" => $this->token,
        );

        $data_array['get_data_categories'] = array(
            "cookie" => $this->token,
            "apicode" => $api_code,
            "entitycode" => "InterCompCateg",
            "packagenumber" => $this->package_number,
            "packagesize" => API_PACKAGE_SIZE,
        );

        $data_array['get_products'] = array(
            "cookie" => $this->token,
            "apicode" => $api_code,
            "entitycode" => PRODUCTS_JSON_DATA_ENDPOINT,
            "packagenumber" => $this->package_number,
            "packagesize" => API_PACKAGE_SIZE,
        );

        $data_array['get_products_description'] = array(
            "cookie" => $this->token,
            "apicode" => $api_code,
            "entitycode" => "GetExtraDescr",
            "packagenumber" => $this->package_number,
            "packagesize" => API_PACKAGE_SIZE,
        );

        $data_array['get_products_quantity'] = array(
            "cookie" => $this->token,
            "apicode" => $api_code,
            "entitycode" => "AllBalancesItem",
            "packagenumber" => $this->package_number,
            "packagesize" => API_PACKAGE_SIZE,
        );

        $data_array['insert_orders'] = array(
            "cookie" => $this->token,
            "apicode" => $api_code,
            "entitycode" => "InsertOrders",
            "extras" => "{}",
        );

        return $data_array;
    }

    public function getToken(): ?string
    {
        $token = null;
        if (file_exists(TOKEN_FILE . ".txt")) {
            $token_file = explode(PHP_EOL, file_get_contents(TOKEN_FILE . ".txt"));
            $token = isset($token_file[1]) ? $token_file[1] : null;
            if ((strtotime('now') + 180) >= $token_file[0]) {
                $token = null;
            }
        }
        return $token;
    }

    public function storeToken($created, $expired): void
    {
        $store_token = false;
        $time = (strtotime($expired) - strtotime($created)) + strtotime('now');
        $store_token = file_put_contents(TOKEN_FILE . ".txt", $time . PHP_EOL . $this->token);
        if (!$store_token) {
            log_error("[API4U] Error:", 'No token provided to store.');
        }
    }
    //End ERP procedures >>

    //Start Integration procedures <<
    public function integrateCategories(): void
    {
//        $response = $this->getData($this->data_array['get_data_categories']);
        $response = "{\"Data\": [{\"categoryId\": \"0c-0c-0c-0c\", \"categoryParentId\": null, \"categoryName\": \"Μπλούζες\", \"categoryForeignName\": \"Shirts\"},{\"categoryId\": \"1c-1c-1c-1c\",  \"categoryParentId\": \"0c-0c-0c-0c\", \"categoryName\": \"Πόλο\", \"categoryForeignName\": \"Polo\"}]}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Categories response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_category->integrateCategory($response['Data']);
        }
    }

    public function integrateAttributeGroups(): void
    {
        //        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"attributeGroupID\": \"1at-1at-1at-1at\",\r\n      \"attributeGroupName\": \"Σεζόν\",\r\n      \"attributeGroupForeignName\": \"Season\"\r\n    },\r\n    {\r\n      \"attributeGroupID\": \"2at-2at-2at-2at\",\r\n      \"attributeGroupName\": \"Σύνθεση\",\r\n      \"attributeGroupForeignName\": \"Composition\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Attribute group response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_attribute->integrateAttributeGroup($response['Data']);
        }
    }

    public function integrateAttributes(): void
    {
        //        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"attributeID\": \"1a1a1a1a\",\r\n      \"attributeGroupID\": \"1at-1at-1at-1at\",\r\n      \"attributeName\": \"Καλοκαίρι 2022\",\r\n      \"attributeForeignName\": \"Summer 2022\"\r\n    },\r\n    {\r\n      \"attributeID\": \"2a2a2a2a\",\r\n      \"attributeGroupID\": \"2at-2at-2at-2at\",\r\n      \"attributeName\": \"Μαλλί\",\r\n      \"attributeForeignName\": \"Wool\"\r\n    },\r\n    {\r\n      \"attributeID\": \"3a3a3a3a\",\r\n      \"attributeGroupID\": \"1at-1at-1at-1at\",\r\n      \"attributeName\": \"Χειμώνας 2022\",\r\n      \"attributeForeignName\": \"Winter 2022\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Attribute response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_attribute->integrateAttribute($response['Data']);
        }
    }

    public function integrateFilterGroups(): void
    {
        //        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"filterGroupID\": \"11111\",\r\n      \"filterGroupName\": \"Χρώμα\",\r\n      \"filterGroupForeignName\": \"Color\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Filters group response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_filter->integrateFilterGroup($response['Data']);
        }
    }

    public function integrateFilters(): void
    {
//        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"filterID\": \"1f-1f-1f-1f\",\r\n      \"filterGroupID\": \"11111\",\r\n      \"filterName\": \"Μπλε\",\r\n      \"filterForeignName\": \"Blue\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Filters response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_filter->integrateFilter($response['Data']);
        }
    }

    public function integrateManufacturers(): void
    {
        //        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"manufacturerId\": \"1man-1man-1man-1man\",\r\n      \"manufacturerName\": \"ZARA\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Manufacturers response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_manufacturer->integrateManufacturer($response['Data']);
        }
    }

    public function integrateOptions(): void
    {
//        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"optionId\": \"11111\",\r\n      \"optionName\": \"Μέγεθος\",\r\n      \"optionForeignName\": \"Size\"\r\n    },\r\n    {\r\n      \"optionId\": \"22222\",\r\n      \"optionName\": \"Χρώμα\",\r\n      \"optionForeignName\": \"Color\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Options response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_option->integrateOption($response['Data']);
        }
    }

    public function integrateOptionValues(): void
    {
//        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"optionValueId\": \"1o-1o-1o-1o\",\r\n      \"optionId\": \"22222\",\r\n      \"filterId\": \"1f-1f-1f-1f\",\r\n      \"optionValueName\": \"Απαλό Μπλέ\",\r\n      \"optionValueForeignName\": \"Light blue\"\r\n    },\r\n    {\r\n      \"optionValueId\": \"2o-2o-2o-2o\",\r\n      \"optionId\": \"22222\",\r\n      \"optionValueName\": \"ZARA/Καφέ\",\r\n      \"optionValueForeignName\": \"ZARA/Brown\"\r\n    },\r\n    {\r\n      \"optionValueId\": \"3o-3o-3o-3o\",\r\n      \"optionId\": \"22222\",\r\n      \"filterId\": \"1f-1f-1f-1f\",\r\n      \"optionValueName\": \"Απαλό Κόκκινο\",\r\n      \"optionValueForeignName\": \"Light Red\"\r\n    },\r\n    {\r\n      \"optionValueId\": \"4o-4o-4o-4o\",\r\n      \"optionId\": \"11111\",\r\n      \"optionValueName\": \"S\",\r\n      \"optionValueForeignName\": \"S\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API4U] Error:", 'Option values response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_option->integrateOptionValue($response['Data']);
        }
    }

    public function integrateProducts(): void
    {
        $flag = 0;
        $response = $this->getData($this->data_array['get_products']);
        $productsDetails = $response["Data"]["Items"];

        // Feature: Use PRODUCTS_JSON_DATA_MAP_POINT to point the array/json where the data are store, via config.php
        // $productsDetails = null;
        // $end_point = PRODUCTS_JSON_DATA_MAP_POINT;
        
        // if (!empty($end_point) && is_array($end_point)) {
        //     if (!empty($end_point[1])) {
        //         if(!empty($response[$end_point[0]][$end_point[1]])) {
        //             $productsDetails = $response[(string) $end_point[0]][(string) $end_point[1]];
        //         } else {
        //             log_error("[API API4U] Error:", 'Please fill $end_point at "config.php", to know where do we find the Products data.'); return;
        //         }
        //     } else if (!empty($end_point[0])) {
        //         if(!empty($response[$end_point[0]])) {
        //             $productsDetails = $response[(string) $end_point[0]];
        //         } else {
        //             log_error("[API API4U] Error:", 'Please fill $end_point at "config.php", to know where do we find the Products data.'); return;
        //         }
        //     }
        //     else log_error("[API API4U] Error:", 'Please fill $end_point at "config.php", to know where do we find the Products data.'); return;
        // } else if (is_null($productsDetails)) {
        //     log_error("[API API4U] Error:", 'Please fill $end_point at "config.php", to know where to find the Products data.'); return;
        // }

        if (!empty($productsDetails)) {
            if (!is_array($response)) $response = json_decode($response, true);
            $this->data_array['get_products']['packagenumber']++;
            $this->active_integration = true;
            $this->model_extension_api4u_product->integrateProduct($productsDetails);
            // $this->model_extension_api4u_product->integrateProductImage();
            // $this->model_extension_api4u_product->productsNewArrival();

            $this->integrateProducts();
        } else {
            $flag++;
        }
        if ($flag > 2) {
            log_error("[API API4U] Error:", 'Api get products response is empty.');
            return;
        }
    }

    public function integrateProductsDescription(): void
    {
//        $response = $this->getData($this->data_array['get_products_description']);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"itemID\": \"11111-11111-11111-11111\",\r\n      \"itemDescription\": \"Πολύ καλή μπλούζα\",\r\n      \"itemForeignDescription\": \"Very good shirt\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API API4U] Warning:", 'Api get products description response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_product->integrateProductDescription($response['Data']);
        }
    }

    public function integrateRelatedOptions(): void
    {
//        $response = $this->getData($this->data_array[]);
        $response = "{\r\n  \"Data\": [\r\n    {\r\n      \"itemID\": \"11111-11111-11111-11111\",\r\n      \"itemAlterCode\": \"252525252525\",\r\n      \"options\": [\r\n        {\r\n          \"optionId\": \"22222\",\r\n          \"optionValueId\": \"1o-1o-1o-1o\"\r\n        },\r\n        {\r\n          \"optionId\": \"11111\",\r\n          \"optionValueId\": \"4o-4o-4o-4o\"\r\n        }\r\n      ],\r\n      \"quantity\": \"5\"\r\n    }\r\n  ]\r\n}";
        if (!is_array($response)) $response = json_decode($response, true);
        if (empty($response) && $this->package_number == 1) {
            log_error("[API API4U] Warning:", 'Api get items quantity response is empty.');
            return;
        }

        if (!empty($response)) {
            $this->active_integration = true;
            $this->model_extension_api4u_related_option->integrateRelatedOptionAndQuantity($response['Data']);
//            $this->model_extension_api4u_related_option->integrateRelatedOptionImage($edited_response);
//            $this->model_extension_api4u_related_option->integrateRelatedOptionImageFiles($store);
        }
    }

    //If products are not in the api response then they are disabled.
    public function updateEshopActiveProducts($store): void
    {
        if (!empty($this->active_api_ids)) {
            $this->model_extension_api4u_product->updateEshopActiveProducts($store, $this->active_api_ids);
        }
    }

    public function editData($action, $response, $store = 0): array
    {
        if (empty($response) && $action != 'get_attributes') {
            return array();
        }

        $response_array = array();
        return $response_array;
    }
    //End Integration procedures >>

    //Start post orders to ERP
    public function postOrders($store): void
    {
        $get_orders = $this->model_extension_api4u_order->getAllOrders($store);
        foreach ($get_orders as $key => $value) {
            $this->data_array['insert_orders']['data'] = "" . json_encode($value['order'], JSON_UNESCAPED_UNICODE) . "";
            $post_response = $this->postData($this->data_array['insert_orders']);
            if (empty($post_response)) {
                log_error("[API4U] Error:", 'Integration post orders API response is empty.');
            } elseif (!isset($post_response['CustomerId'])) {
                log_error("[API4U] Error:", "{$post_response['Message']}: {$post_response['EntityCode']}");
            } else {
                $data = array(
                    'customer_id' => $value['customer']['eshop_customer_id'],
                    'order_id' => $key, 'customer_erp_id' => $post_response['CustomerId'],
                    'api_custom_field' => json_encode(array('entity_id' => $post_response['EntityId'], 'entity_code' => $post_response['EntityCode']), JSON_UNESCAPED_UNICODE),
                );
                $this->model_extension_api4u_order->updateOrder($data);
                if (!$value['customer']['erp_customer']) {
                    if ($value['customer']['eshop_customer_id'] != 0) {
                        $this->model_extension_api4u_customer->updateCustomer($data);
                    } else {
                        $this->model_extension_api4u_customer->updateGuestCustomer($data);
                    }
                }
            }
        }
    }
    //End post orders to ERP

    //Start change status of shipped orders and send email
    public function shippedOrders($store): void
    {
        $get_orders = $this->model_extension_api4u_order->getShipOrders($store);
        if (empty($get_orders)) {
            return;
        }

        $this->data_array['track_orders']['extras'] = "" . json_encode($get_orders, JSON_UNESCAPED_UNICODE) . "";
        $get_response = $this->getData($this->data_array['track_orders']);
        if (empty($get_response)) {
            log_error("[API4U] Error:", 'Integration shipped Api response is empty.');
        } else {
            foreach ($get_response['Data']['TrackOrders'] as $value) {
                if ((!isset($value['Voucher']) || $value['Voucher'] == '') && (!isset($value['transportCompany']) || $value['transportCompany'] == '')) {
                    continue;
                }
                $data = array(
                    'entity_id' => $value['OrderID'],
                    'company' => $value['transportCompany'],
                    'voucher' => $value['Voucher'],
                );

                $shipped_order = $this->model_extension_api4u_order->getShippedOrder($data);
                $url = NOTIFY_SHIPPED_ORDER_URL . "&api_token={$this->session->getId()}&store_id=$store&order_id={$shipped_order['order_id']}";
                $shipped_data = array(
                    'order_status_id' => $shipped_order['order_status_id'],
                    'notify' => 1,
                    'override' => 0,
                    'append' => 0,
                    'comment' => $value['Voucher'] ?? '',
                    'content_type' => 1,
                );

                $shipped_response = $this->postData($shipped_data, $url);
                if ($shipped_response['success']) {
                    $this->model_extension_api4u_order->updateShippedOrder($data);
                }
            }
        }
    }
    //End change status of shipped orders and send email

    //Start export Excel
    public function exportExcel($store): void
    {
        $row_data_array = array();
        $row_data_without_image_array = array();
        $image_array = array();
        $sheet = 'Φύλλο 1';
        $header = array(
            "ΚΩΔΙΚΟΣ ΕΙΔΟΥΣ" => "string",
            "ΠΕΡΙΓΡΑΦΗ ΕΙΔΟΥΣ" => "string",
            "ΛΙΑΝΙΚΗ ΤΙΜΗ" => "euro",
            "ΤΙΜΗ ΠΩΛΗΣΗΣ" => "euro",
            "ΗΜΕΡΟΜΗΝΙΑ (ΠΟΥ ΒΓΗΚΕ LIVE)" => "string",
            "ΠΕΡΙΓΡΑΦΗ ΧΡΩΜΑΤΟΣ" => "string",
            "ΚΑΤΑΣΤΑΣΗ ΠΡΟΙΟΝΤΟΣ" => "string",
            "SEASON" => "string",
            "LINK ΦΩΤΟΓΡΑΦΙΑΣ1" => "string",
            "LINK ΦΩΤΟΓΡΑΦΙΑΣ2" => "string",
            "LINK ΦΩΤΟΓΡΑΦΙΑΣ3" => "string",
            "LINK ΦΩΤΟΓΡΑΦΙΑΣ4" => "string",
            "LINK ΦΩΤΟΓΡΑΦΙΑΣ5" => "string",
            "LINK ΦΩΤΟΓΡΑΦΙΑΣ6" => "string",
            "ΥΠΟΛΟΙΠΟ" => "integer",
        );
        $title = array('ΑΡΧΕΙΟ PRODUCTLIST');
        $date = array(date('d/m/Y', time()));
        $day = array(date('l d/m/Y', time()), '', '', '', '', '', '', '', '', '', '', '', '', '', '');
        $no_image = array('WITHOUT PHOTOS', '', '', '', '', '', '', '', '', '', '', '', '', '', '');
        $result = $this->model_extension_api4u_export_excel->index($store);
        foreach ($result->rows as $value) {
            $model = $value['model'];
            $name = $value['name'];
            $price = $value['price'];
            $special_price = $value['special_price'];
            $date_added = $value['date_added'];
            $filter_name = $value['filter_name'];
            $image = $value['image'];
            $images = explode(',', $value['images']);
            $quantity = $value['quantity'];
            $status = $value['status'];
            $season = $value['season'];
            if (is_file(DIR_IMAGE . $image) && $image != 'no_image.png') {
                $image = 'https://navygreen-eshop.gr/image/cachewebp/' . str_replace('.jpg', '', $image) . '-710x800.webp';
                foreach ($images as $img) {
                    if (is_file(DIR_IMAGE . $img)) {
                        $image_array[] = 'https://navygreen-eshop.gr/image/cachewebp/' . str_replace('.jpg', '', $img) . '-88x110.webp';
                    }
                }
                $row_data_array[] = array($model, $name, $price, $special_price, $date_added, $filter_name, $status, $season, $image, $image_array[0] ?? null, $image_array[1] ?? null, $image_array[2] ?? null, $image_array[3] ?? null, $image_array[4] ?? null, $quantity);
            } else {
                $row_data_without_image_array[] = array($model, $name, $price, $special_price, $date_added, $filter_name, $status, $season, $image, $image_array[0] ?? null, $image_array[1] ?? null, $image_array[2] ?? null, $image_array[3] ?? null, $image_array[4] ?? null, $quantity);
            }
        }

        $writer = new XLSXWriter();
        $writer->setSubject('Some Subject');
        $writer->setAuthor('API4U');
        $writer->setCompany('Navy Green');
        $writer->setTempDir(sys_get_temp_dir());
        $writer->writeSheetRow($sheet, $title, 0, array('widths' => [19, 33.14, 10.14, 10.14, 17, 12.71, 13, 12.71, 16.43, 16.43, 16.43, 16.43, 16.43, 16.43, 10.57], 'font' => 'Calibri', 'font-size' => 16, 'font-style' => 'bold,underline'));
        $writer->writeSheetRow($sheet, $date, 1, array('font' => 'Calibri', 'font-size' => 11, 'font-style' => 'italic'));
        $writer->writeSheetRow($sheet, $day, 2, array('font' => 'Calibri', 'font-size' => 16, 'font-style' => 'bold,underline', 'halign' => 'center', 'valign' => 'center', 'border' => 'top,bottom,left,right', 'border-style' => 'thin'));
        $writer->writeSheetHeader($sheet, $header, 3, array('height' => 30, 'font' => 'Calibri', 'font-size' => 11, 'font-style' => 'bold', 'wrap_text' => true, 'halign' => 'center', 'valign' => 'center', 'border' => 'top,bottom,left,right', 'border-style' => 'thin'));
        foreach ($row_data_array as $row) {
            $writer->writeSheetRow($sheet, $row, null);
        }

        $writer->writeSheetRow($sheet, $no_image, null, array('font' => 'Calibri', 'font-size' => 16, 'font-style' => 'bold,underline', 'halign' => 'center', 'valign' => 'center', 'border' => 'top,bottom,left,right', 'border-style' => 'thin'));
        $writer->markMergedCell($sheet, $start_row = null, $start_col = 0, $end_row = null, $end_col = 12);
        foreach ($row_data_without_image_array as $row) {
            $writer->writeSheetRow($sheet, $row, null);
        }

        $writer->markMergedCell($sheet, $start_row = 2, $start_col = 0, $end_row = 2, $end_col = 12);
        $store = $store == 0 ? 'greek' : 'cyprian';
        $writer->writeToFile(DIR_IMAGE . 'catalog/product-upload/' . date('d-m-Y(H.i.s)', time()) . '-store_' . $store . '.xlsx');
    }
    //End export Excel
}
