<?php
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

class ModelExtensionApi4uOrder extends Model
{
    public function getAllOrders($store = 0): array
    {
        $response_array = array();
        $cod_fee = 0;
        $shipping_fee = 0;
        usleep(rand(30000, 100000));
$SQL = "SELECT `method_data`
                FROM `" . DB_PREFIX . "xfeepro`;";
        $result = db_query_handler($this->db, $SQL);
        if ($result->num_rows)
        {
            $cod_fee = json_decode($result->rows[0]['method_data'], TRUE)['cost'];
        }

        usleep(rand(30000, 100000));
$SQL = "SELECT `O`.`order_id`, `O`.`email`, `O`.`api_id`, `O`.`payment_custom_field`, `O`.`payment_code`, `O`.`payment_address_1`, `O`.`payment_postcode`,
			           `O`.`payment_zone`, `O`.`payment_city`, `O`.`shipping_firstname`, `O`.`shipping_lastname`, `O`.`shipping_address_1`,
			           `O`.`shipping_address_2`, `O`.`shipping_postcode`, `O`.`shipping_zone`, `O`.`shipping_city`, `O`.`firstname`, `O`.`lastname`,
			           `O`.`shipping_country`, `O`.`shipping_code`, `O`.`comment`, `OP`.`quantity`,`P`. `price`, `O`.`date_added`,
			           (SELECT `model` FROM `" . DB_PREFIX . "product` where `product_id` = `OP`.`product_id`) as model,
			           (SELECT `api_custom_field` FROM `" . DB_PREFIX . "product` where `product_id` = `OP`.`product_id`) as custom_fields,
                       (SELECT `price` FROM `" . DB_PREFIX . "product_special` where `product_id` = `OP`.`product_id` limit 1) as special_price,
			           `OP`.`sku`, `O`.`telephone`, `O`.`firstname`, `O`.`lastname`, `O`.`shipping_country_id`, `O`.`store_id`, `O`.`payment_company`
                FROM `" . DB_PREFIX . "order` `O`
                INNER JOIN `" . DB_PREFIX . "order_product` `OP` ON `OP`.`order_id` = `O`.`order_id`
                INNER JOIN `" . DB_PREFIX . "product` P ON `P`.`product_id` = `OP`.`product_id`
                WHERE `O`.`order_status_id` IN (1,2,5,15) AND `O`.`store_id` = " . (int)$store . " AND `O`.`date_added` < (NOW() - interval 3 minute);";
        $result = db_query_handler($this->db, $SQL);
        foreach ($result->rows as $row)
        {
            $sku = $row['sku'] ?? null;
            if ($sku == '' || !isset($sku))
            {
                continue;
            }

            $order_id = $row['order_id'];
            $email = $row['email'] ?? null;
            $name = $row['firstname'] . ' ' . $row['lastname'];
            $phone = $row['telephone'] ?? null;
            $street = $row['payment_address_1'] ?? null;
            $prefecture = $row['payment_zone'] ?? null;
            $postal_code = $row['payment_postcode'] ?? null;
            $city = $row['payment_city'] ?? null;
            $shipping_name = $row['shipping_firstname'] . ' ' . $row['shipping_lastname'];
            $shipping_address = $row['shipping_address_1'] ?? null;
            $shipping_prefecture = $row['shipping_zone'] ?? null;
            $shipping_postal_code = $row['shipping_postcode'] ?? null;
            $shipping_city = $row['shipping_city'] ?? null;
            $customer_id = $row['api_id'] ?? null;
            $item_code = isset($sku) && $sku != '' ? $sku : $row['model'] ?? null;
            $item_code_parameter = isset($sku) && $sku != '' ? 'CenlItemAlterCode' : 'CenlItemCode';
            $remarks = $row['comment'] ?? null;
            $cenl_price = $row['price'] ?? null;
            $cenl_price_after_disc = $row['special_price'] ?? null;
            $cenl_a_measurement_qty = $row['quantity'] ?? null;
            $store_id = $row['store_id'] ?? null;
            $payment_method = json_decode($row['payment_custom_field'] ?? null, TRUE);
            $shipping_code = $row['shipping_code'] ?? null;
            $payment_company = $row['payment_company'] ?? null;
            $street_number = $row['shipping_address_2'] ?? "";

            usleep(rand(30000, 100000));
$SQL = "SELECT * 
                    FROM `" . DB_PREFIX . "xoffer_total`
                    WHERE `order_id` = " . (int)$order_id . ";";
            $result = db_query_handler($this->db, $SQL);
            if ($result->row)
            {
                $remarks .= '{Συσκευασία δώρου}';
            }

            $pmmtcode = 0;
            switch ($row['payment_code'])
            {
                case 'cod':
                    $pmmtcode = !$store_id ? '000017' : '000006';
                    break;
                default :
                    $cod_fee = 0;
                    $pmmtcode = !$store_id ? '000016' : '000005';
                    break;
            }

            if (isset($shipping_code) && strpos($shipping_code, 'xshippingpro.xshippingpro') !== false)
            {
                preg_match('/\d/', $shipping_code, $output_array);
                usleep(rand(30000, 100000));
$SQL = "SELECT json_extract(`method_data`, '$.cost') AS shipping_fee
                        FROM `" . DB_PREFIX . "xshippingpro`
                        WHERE `id` = " . (int)$output_array[0] . " AND json_extract(`method_data`, '$.store') LIKE '%" . (int)$store_id . "%'";
                $result = db_query_handler($this->db, $SQL);
                if ($result->num_rows)
                {
                    $shipping_fee = (float)trim($result->row['shipping_fee'], '"');
                }
            }

            if (!array_key_exists($order_id, $response_array))
            {
                $response_array[$order_id]['order'] = array(
                    'CstmEmail' => $email,
                    'EshopOrderCode' => $order_id + 120000,
                    'CstmName' => $name,
                    'Email' => $email,
                    'PmmtCode' => $pmmtcode,
                    'Street' => $street,
                    'Remarks' => $remarks
                );

                if ($store_id == 0)
                {
                    if (trim($payment_method[1], ' ') != "")
                    {
                        $response_array[$order_id]['order']['SeriesCode'] = '17ΠΑΜ';
                        $response_array[$order_id]['order']['CustomerTIN'] = preg_replace('/[^0-9]/i', '', $payment_method[1]);
                        $response_array[$order_id]['order']['Doy'] = (int)$payment_method[3];
                        $response_array[$order_id]['order']['ProfessionCode'] = (int)$payment_method[4];
                        $response_array[$order_id]['order']['DistinctiveTitle'] = "$payment_company";
                    }
                    else
                    {
                        $response_array[$order_id]['order']['SeriesCode'] = '17ΠΑΡ';
                    }
                }
                else
                {
                    $response_array[$order_id]['order']['SeriesCode'] = '31ΠΑΡ';
                }

                if ($cod_fee != 0)
                {
                    $response_array[$order_id]['order']['DeliveryCharge'] = $cod_fee;
                }

                if ($shipping_fee != 0)
                {
                    $response_array[$order_id]['ExtraCharge'] = $shipping_fee;
                }

                if (!isset($customer_id))
                {
                    usleep(rand(30000, 100000));
$SQL = "SELECT `O`.`email`, `O`.`api_id`, `C`.api_id AS customer_api_id, `O`.`customer_id`
                            FROM `" . DB_PREFIX . "order` O
                            LEFT JOIN `" . DB_PREFIX . "customer` C ON `C`.`customer_id` = `O`.`customer_id`
                            WHERE `O`.email = '$email' AND (`O`.`api_id` IS NOT NULL || `C`.`api_id` IS NOT NULL);";
                    $result = db_query_handler($this->db, $SQL);
                    $customer_id = !isset($result->row['customer_id']) || $result->row['customer_id'] == '' ? null : $result->row['customer_id'];
                    $customer_api_id = isset($result->row['customer_api_id']) ? $result->row['customer_api_id'] : $result->row['api_id'] ?? null;
                    if (!isset($customer_api_id))
                    {
                        $response_array[$order_id]['order']['StreetNumber'] = $street_number;
                        $response_array[$order_id]['order']['PostalCode'] = $postal_code;
                        $response_array[$order_id]['order']['City'] = $city;
                        $response_array[$order_id]['order']['Prefecture'] = $prefecture;
                        $response_array[$order_id]['order']['Region'] = $prefecture;
                        $response_array[$order_id]['order']['Email'] = $email;
                        $response_array[$order_id]['order']['Phone1'] = $phone;
                        $response_array[$order_id]['customer'] = array('erp_customer' => 0, 'customer_api_id' => null, 'eshop_customer_id' => $customer_id);
                    }
                    else
                    {
                        $response_array[$order_id]['customer'] = array('erp_customer' => 1, 'customer_api_id' => $customer_api_id, 'eshop_customer_id' => $customer_id);
                    }
                }

                if ($shipping_address != $street || $city != $shipping_city || $name != $shipping_name || $postal_code != $shipping_postal_code || $prefecture != $shipping_prefecture)
                {
                    $response_array[$order_id]['order']['SendToOtherAddress'] = '1';
                    $response_array[$order_id]['order']['OrderEmail'] = $email;
                    $response_array[$order_id]['order']['OrderName'] = $shipping_name;
                    $response_array[$order_id]['order']['OrderStreet'] = $shipping_address;
                    $response_array[$order_id]['order']['OrderStreetNumber'] = $street_number;
                    $response_array[$order_id]['order']['OrderPostalCode'] = $shipping_postal_code;
                    $response_array[$order_id]['order']['OrderPrefecture'] = $shipping_prefecture;
                    $response_array[$order_id]['order']['OrderCity'] = $shipping_city;
                    $response_array[$order_id]['order']['OrderRegion'] = $shipping_prefecture;
                    $response_array[$order_id]['order']['OrderPhone1'] = $phone;
                }

                $response_array[$order_id]['order']['StreetNumber'] = $street_number;
                usleep(rand(30000, 100000));
$SQL = "SELECT `value`
                        FROM `" . DB_PREFIX . "order_total`
                        WHERE `order_id` = " . (int)$order_id . " AND `code` = 'xfeepro' AND `value` < 0
                        LIMIT 1;";
                $result = db_query_handler($this->db, $SQL);
                if ($row = $result->row)
                {
                    $response_array[$order_id]['order']['OrderDiscountValue'] = number_format(abs($row['value']), 2);
                }
            }
            $response_array[$order_id]['order']['Lines'][] = array(
                "$item_code_parameter" => $item_code,
                "CenlPrice" => $cenl_price,
                "CenlPriceAfterDisc" => $cenl_price_after_disc,
                "CenlAMeasurementQty" => $cenl_a_measurement_qty
            );
        }
        return $response_array;
    }

    public function getOrder($data): array
    {
        $response_array = array();
        $cod_fee = 0;
        $shipping_fee = 0;
        usleep(rand(30000, 100000));
$SQL = "SELECT `method_data`
                FROM `" . DB_PREFIX . "xfeepro`;";
        $result = db_query_handler($this->db, $SQL);
        if ($result->num_rows)
        {
            $cod_fee = json_decode($result->rows[0]['method_data'], TRUE)['cost'];
        }

        usleep(rand(30000, 100000));
$SQL = "SELECT `O`.`email`, `O`.`api_id`, `O`.`payment_custom_field`, `O`.`payment_code`, `O`.`payment_address_1`, `O`.`payment_postcode`,
			           `O`.`payment_zone`, `O`.`payment_city`, `O`.`shipping_firstname`, `O`.`shipping_lastname`, `O`.`shipping_address_1`,
			           `O`.`shipping_address_2`, `O`.`shipping_postcode`, `O`.`shipping_zone`, `O`.`shipping_city`, `O`.`firstname`, `O`.`lastname`,
			           `O`.`shipping_country`, `O`.`shipping_code`, `O`.`comment`, `OP`.`quantity`,`P`. `price`, `O`.`date_added`,
			           (SELECT `model` FROM `" . DB_PREFIX . "product` where `product_id` = `OP`.`product_id`) as model,
			           (SELECT `api_custom_field` FROM `" . DB_PREFIX . "product` where `product_id` = `OP`.`product_id`) as custom_fields,
                       (SELECT `price` FROM `" . DB_PREFIX . "product_special` where `product_id` = `OP`.`product_id` limit 1) as special_price,
			           `OP`.`sku`, `O`.`telephone`, `O`.`firstname`, `O`.`lastname`, `O`.`shipping_country_id`, `O`.`store_id`, `O`.`payment_company`
                FROM `" . DB_PREFIX . "order` `O`
                INNER JOIN `" . DB_PREFIX . "order_product` `OP` ON `OP`.`order_id` = `O`.`order_id`
                INNER JOIN `" . DB_PREFIX . "product` P ON `P`.`product_id` = `OP`.`product_id`
                WHERE `O`.`order_id` = " . (int)$data['order_id'] . " AND `O`.`order_status_id` IN (1,2,5,15);";
        $result = db_query_handler($this->db, $SQL);

        foreach ($result->rows as $row)
        {
            $sku = $row['sku'] ?? null;
            if ($sku == '' || !isset($sku))
            {
                continue;
            }

            $email = $row['email'] ?? null;
            $name = $row['firstname'] . ' ' . $row['lastname'];
            $phone = $row['telephone'] ?? null;
            $street = $row['payment_address_1'] ?? null;
            $prefecture = $row['payment_zone'] ?? null;
            $postal_code = $row['payment_postcode'] ?? null;
            $city = $row['payment_city'] ?? null;
            $shipping_name = $row['shipping_firstname'] . ' ' . $row['shipping_lastname'];
            $shipping_address = $row['shipping_address_1'] ?? null;
            $shipping_prefecture = $row['shipping_zone'] ?? null;
            $shipping_postal_code = $row['shipping_postcode'] ?? null;
            $shipping_city = $row['shipping_city'] ?? null;
            $customer_id = $row['api_id'] ?? null;
            $item_code = isset($sku) && $sku != '' ? $sku : $row['model'] ?? null;
            $item_code_parameter = isset($sku) && $sku != '' ? 'CenlItemAlterCode' : 'CenlItemCode';
            $remarks = $row['comment'] ?? null;
            $cenl_price = $row['price'] ?? null;
            $cenl_price_after_disc = $row['special_price'] ?? null;
            $cenl_a_measurement_qty = $row['quantity'] ?? null;
            $store_id = $row['store_id'] ?? null;
            $payment_method = json_decode($row['payment_custom_field'] ?? null, TRUE);
            $shipping_code = $row['shipping_code'] ?? null;
            $payment_company = $row['payment_company'] ?? null;
            $street_number = $row['shipping_address_2'] ?? "";
            usleep(rand(30000, 100000));
$SQL = "SELECT * 
                    FROM `" . DB_PREFIX . "xoffer_total`
                    WHERE `order_id` = " . (int)$data['order_id'] . ";";
            $result = db_query_handler($this->db, $SQL);
            if ($result->row)
            {
                $remarks .= '{Συσκευασία δώρου}';
            }

            if (!array_key_exists($data['order_id'], $response_array))
            {
                $pmmtcode = 0;
                switch ($row['payment_code'])
                {
                    case 'cod':
                        $pmmtcode = !$store_id ? '000017' : '000006';
                        $response_array[$data['order_id']]['PmmtCode'] = $pmmtcode;
                        break;
                    default :
                        $cod_fee = 0;
                        $pmmtcode = !$store_id ? '000016' : '000005';
                        break;
                }

                if (isset($shipping_code) && strpos($shipping_code, 'xshippingpro.xshippingpro') !== false)
                {
                    preg_match('/\d/', $shipping_code, $output_array);
                    usleep(rand(30000, 100000));
$SQL = "SELECT json_extract(`method_data`, '$.cost') AS shipping_fee
                            FROM `" . DB_PREFIX . "xshippingpro`
                            WHERE `id` = " . (int)$output_array[0] . " AND json_extract(`method_data`, '$.store') LIKE '%" . (int)$store_id . "%'";
                    $result = db_query_handler($this->db, $SQL);
                    if ($result->num_rows)
                    {
                        $shipping_fee = (float)trim($result->row['shipping_fee'], '"');
                    }
                }

                $response_array[$data['order_id']] = array(
                    'CstmEmail' => $email,
                    'EshopOrderCode' => $data['order_id'] + 120000,
                    'CstmName' => $name,
                    'Email' => $email,
                    'PmmtCode' => $pmmtcode,
                    'Street' => $street,
                    'Remarks' => $remarks
                );

                if ($store_id == 0)
                {
                    if (trim($payment_method[1], ' ') != "")
                    {
                        $response_array[$data['order_id']]['SeriesCode'] = '17ΠΑΜ';
                        $response_array[$data['order_id']]['CustomerTIN'] = preg_replace('/[^0-9]/i', '', $payment_method[1]);
                        $response_array[$data['order_id']]['Doy'] = (int)$payment_method[3];
                        $response_array[$data['order_id']]['ProfessionCode'] = (int)$payment_method[4];
                        $response_array[$data['order_id']]['DistinctiveTitle'] = "$payment_company";
                    }
                    else
                    {
                        $response_array[$data['order_id']]['SeriesCode'] = '17ΠΑΡ';
                    }
                }
                else
                {
                    $response_array[$data['order_id']]['SeriesCode'] = '31ΠΑΡ';
                }

                if ($cod_fee != 0)
                {
                    $response_array[$data['order_id']]['DeliveryCharge'] = $cod_fee;
                }

                if ($shipping_fee != 0)
                {
                    $response_array[$data['order_id']]['ExtraCharge'] = $shipping_fee;
                }

                if (!isset($customer_id))
                {
                    usleep(rand(30000, 100000));
$SQL = "SELECT `O`.`email`, `O`.`api_id`, `C`.api_id AS customer_api_id, `O`.`customer_id`
                            FROM `" . DB_PREFIX . "order` O
                            LEFT JOIN `" . DB_PREFIX . "customer` C ON `C`.`customer_id` = `O`.`customer_id`
                            WHERE `O`.email = '$email' AND (`O`.`api_id` IS NOT NULL || `C`.`api_id` IS NOT NULL);";
                    $result = db_query_handler($this->db, $SQL);
                    $customer_id = !isset($result->row['customer_id']) || !$result->row['customer_id'] ? null : $result->row['customer_id'];
                    $customer_api_id = isset($result->row['customer_api_id']) ? $result->row['customer_api_id'] : $result->row['api_id'] ?? null;
                    if (!isset($customer_api_id))
                    {
                        $response_array[$data['order_id']]['StreetNumber'] = '';
                        $response_array[$data['order_id']]['PostalCode'] = $postal_code;
                        $response_array[$data['order_id']]['City'] = $city;
                        $response_array[$data['order_id']]['Prefecture'] = $prefecture;
                        $response_array[$data['order_id']]['Region'] = $prefecture;
                        $response_array[$data['order_id']]['Email'] = $email;
                        $response_array[$data['order_id']]['Phone1'] = $phone;
                        $response_array['customer'] = array('erp_customer' => 0, 'customer_api_id' => null, 'eshop_customer_id' => $customer_id);
                    }
                    else
                    {
                        $response_array['customer'] = array('erp_customer' => 1, 'customer_api_id' => $customer_api_id, 'eshop_customer_id' => $customer_id);
                    }
                }

                if ($shipping_address != $street || $city != $shipping_city || $name != $shipping_name || $postal_code != $shipping_postal_code || $prefecture != $shipping_prefecture)
                {
                    $response_array[$data['order_id']]['SendToOtherAddress'] = '1';
                    $response_array[$data['order_id']]['OrderEmail'] = $email;
                    $response_array[$data['order_id']]['OrderName'] = $shipping_name;
                    $response_array[$data['order_id']]['OrderStreet'] = $shipping_address;
                    $response_array[$data['order_id']]['OrderStreetNumber'] = $street_number;
                    $response_array[$data['order_id']]['OrderPostalCode'] = $shipping_postal_code;
                    $response_array[$data['order_id']]['OrderPrefecture'] = $shipping_prefecture;
                    $response_array[$data['order_id']]['OrderCity'] = $shipping_city;
                    $response_array[$data['order_id']]['OrderRegion'] = $shipping_prefecture;
                    $response_array[$data['order_id']]['OrderPhone1'] = $phone;
                }

                $response_array[$data['order_id']]['StreetNumber'] = $street_number;
                usleep(rand(30000, 100000));
$SQL = "SELECT `value`
                        FROM `" . DB_PREFIX . "order_total`
                        WHERE `order_id` = " . (int)$data['order_id'] . " AND `code` = 'xfeepro' AND `value` < 0
                        LIMIT 1;";
                $result = db_query_handler($this->db, $SQL);
                if ($row = $result->row)
                {
                    $response_array[$data['order_id']]['OrderDiscountValue'] = number_format(abs($row['value']), 2);
                }
            }

            $response_array[$data['order_id']]['Lines'][] = array(
                "$item_code_parameter" => $item_code,
                "CenlPrice" => $cenl_price,
                "CenlPriceAfterDisc" => $cenl_price_after_disc,
                "CenlAMeasurementQty" => $cenl_a_measurement_qty
            );
        }

        return $response_array;
    }

    public function getShipOrders($store): array
    {
        $response_array = array();

        usleep(rand(30000, 100000));
$SQL = "SELECT `api_custom_field`
                FROM `" . DB_PREFIX . "order`
                WHERE `order_status_id` = 17 AND `store_id` = " . (int)$store . ";";
        $result = db_query_handler($this->db, $SQL);

        foreach ($result->rows as $row)
        {
            $api_custom_field = json_decode($row['api_custom_field'], TRUE) ?? null;
            if ($api_custom_field == '' || !isset($api_custom_field) || !isset($api_custom_field['entity_id']))
            {
                continue;
            }

            $response_array['@orderID'][] = $api_custom_field['entity_id'];
        }
        return $response_array;
    }

    public function getShippedOrder($data): array
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on get shipped order.');
            return array();
        }

        usleep(rand(30000, 100000));
$SQL = "SELECT `order_id`
                FROM `" . DB_PREFIX . "order`
                WHERE `api_custom_field` LIKE '%" . $this->db->escape($data['entity_id']) . "%';";
        $result = db_query_handler($this->db, $SQL);
        $order_id = $result->row['order_id'];
        if (!isset($order_id) || $order_id == '')
        {
            return array();
        }

        if (isset($data['voucher']) && $data['voucher'] != '')
        {
            $order_status_id = 18;
        }
        else
        {
            $order_status_id = 3;
        }

        $return_array = array(
            'order_id' => $order_id,
            'order_status_id' => $order_status_id
        );

        return $return_array;
    }

    public function updateOrder($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on update order.');
            return;
        }

        usleep(rand(30000, 100000));
$SQL = "UPDATE `" . DB_PREFIX . "order`
                SET `api_custom_field` = '" . $this->db->escape($data['api_custom_field']) . "',
                    `order_status_id` = 17,
                    `date_modified` = NOW()
                WHERE `order_id` = " . (int)$data['order_id'] . ";";
        db_query_handler($this->db, $SQL);

        usleep(rand(30000, 100000));
$SQL = "INSERT INTO `" . DB_PREFIX . "order_history`
                SET `order_id` = " . (int)$data['order_id'] . ",
                    `order_status_id` = 17,
                    `notify` = 0,
                    `date_added` = NOW();";
        db_query_handler($this->db, $SQL);
    }

    public function updateShippedOrder($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on update shipped order.');
            return;
        }

        usleep(rand(30000, 100000));
$SQL = "SELECT `order_id`
                FROM `" . DB_PREFIX . "order`
                WHERE `api_custom_field` LIKE '%" . $this->db->escape($data['entity_id']) . "%';";
        $result = db_query_handler($this->db, $SQL);
        $order_id = $result->row['order_id'];
        if (!isset($order_id) || $order_id == '')
        {
            return;
        }

        if (isset($data['voucher']) && $data['voucher'] != '')
        {
            $order_status_id = 18;
        }
        else
        {
            $order_status_id = 3;
        }

        usleep(rand(30000, 100000));
$SQL = "UPDATE `" . DB_PREFIX . "order`
                SET `order_status_id` = '$order_status_id',
                    `date_modified` = NOW()
                WHERE `order_id` = " . (int)$order_id . ";";
        db_query_handler($this->db, $SQL);
    }

    public function checkOrder($data): array
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on check order.');
            return array();
        }

        $response_array = array('status' => 1);
        usleep(rand(30000, 100000));
$SQL = "SELECT `OP`.`sku`, `P`.`status`, `R`.`quantity`
                FROM `" . DB_PREFIX . "order` `O`
                LEFT JOIN `" . DB_PREFIX . "order_product` `OP` ON `OP`.`order_id` = `O`.`order_id`
                LEFT JOIN `" . DB_PREFIX . "product` P ON `P`.`product_id` = `OP`.`product_id`
                LEFT JOIN `nvgrntbl_relatedoptions` `R` ON `R`.`sku` = `OP`.`sku` 
                    AND `R`.`product_id` = `P`.`product_id`
                WHERE `O`.`order_id` = " . (int)$data['order_id'] . ";";
        $result = db_query_handler($this->db, $SQL);

        foreach ($result->rows as $row)
        {
            $status = $row['status'] ?? 0;
            $sku = $row['sku'] ?? null;
            $quantity = $row['quantity'] ?? 0;
            if ($sku == '' || !isset($sku) || !$status || !$quantity)
            {
                return array();
            }
        }
        return $response_array;
    }
}