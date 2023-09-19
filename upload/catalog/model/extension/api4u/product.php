<?php
require '../../../system/library/api4u/config.php';

class ModelExtensionApi4uProduct extends Model
{
    public $language_ids = array();
    public $customer_group_ids = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language_ids = $this->config->get('config_language_ids');
        $this->customer_group_ids = $this->config->get('config_customer_group_ids');
    }

    public function integrateProduct($data = array()): void
    {
        $store = 0;
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on product.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateProduct');
            exit();
        }

        if(JSON_DECODE_PRODUCT == true) if(!is_array($data)) $value = json_decode($data, TRUE);
        foreach ($data as $value)
        {
            if(JSON_DECODE_PRODUCT == true) if(!is_array($value)) $value = json_decode($value, TRUE);
            $last_inserted_id = 0;
            $model = $value['ITEMCODE'] ?? null;
            $api_id = $value['ITEMID'] ?? null;
            if (!isset($model) || !isset($api_id))
            {
                continue;
            }

            $sku = $value['sku'] ?? '';
            $upc = $value['upc'] ?? '';
            $ean = $value['ean'] ?? '';
            $jan = $value['jan'] ?? '';
            $isbn = $value['isbn'] ?? '';
            $mpn = $value['mpn'] ?? '';
            $location = $value['location'] ?? '';
            $quantity = $value['Balance'] ?? '0';
            $stock_status_id = $quantity > 9 ? '7' : '8';
            //TODO image
            $image = isset($value['image']) ? 'catalog/product-upload/' . $value['image'] : 'no_image.png';
            
            if ($image != 'no_image.png')
            {
                if (!file_exists(DIR_IMAGE . $image))
                {
                    //If the main colour image does not exist take the image of the next colour.
                    $image = main_image_selection($this->db, $model, $image);
                }
            }

            $manufacturer_id = isset($value['itemManufacturerId']) ? "(SELECT `manufacturer_id` FROM " . DB_PREFIX . "manufacturer WHERE `api_id` = '" . $this->db->escape($value['itemManufacturerId']) . "' )" : '0';
            $shipping = $value['shipping'] ?? '1';
            
            if ($value['INITIALRTLPRICE'] != 0) { 
                $price = $value['INITIALRTLPRICE']; 
                $special_price = $value['RETAILPRICE'];
            }
            else { 
                $price = $value['RETAILPRICE'];
                $special_price = null; 
            }

            $points = $value['points'] ?? '0';
            $tax_class_id = $value['taxClassId'] ?? '0';
            $weight = $value['weight'] ?? '0.00000000';
            $weight_class_id = $value['weight_class_id'] ?? '1';
            $length = $value['length'] ?? '0.00000000';
            $width = $value['width'] ?? '0.00000000';
            $height = $value['height'] ?? '0.00000000';
            $length_class_id = $value['length_class_id'] ?? '1';
            $subtract = $value['subtract'] ?? '1';
            $minimum = $value['minimum'] ?? '1';
            $sort_order = $value['sortOrder'] ?? '1';
            $status = $quantity > 0 && $image != 'no_image.png' ? 1 : 0;
            $viewed = $value['viewed'] ?? '0';
            $name = $value['ITEMNAME'] ?? null;
            $description = $value['DETAILEDDESCR'] ?? null;
            $foreign_name = $value['itemForeignName'] ?? $name;
            $foreign_description = $value['foreignDescription'] ?? $description;
            $product_to_category = $value['itemTreeCategoryId'] ?? null;
            $api_custom_field = $value['apiCustomField'] ?? $store;
            $filters = $value['filters'] ?? array();
            $options = $value['options'] ?? array();
            $attribute = $value['attributes'] ?? array();
            $date_modified = $value['dateModified'] ?? 'NOW()';
            if ($row = check_existence($this->db, DB_PREFIX . 'product', 'api_id', $api_id, 'api_id'))
            {
                usleep(rand(100000, 250000));
                $order = $store ? 'DESC' : 'ASC';
                $SQL = "UPDATE `" . DB_PREFIX . "product`
                        SET `sku` = '" . $this->db->escape($sku) . "',
                            `upc` = '" . $this->db->escape($upc) . "',
                            `ean` = '" . $this->db->escape($ean) . "',
                            `jan` = '" . $this->db->escape($jan) . "',
                            `isbn`= '" . $this->db->escape($isbn) . "',
                            `mpn` = '" . $this->db->escape($mpn) . "',
                            `location` = '" . $this->db->escape($location) . "',
                            `quantity` = '" . (int)$quantity . "',
                            `model` = '" . $this->db->escape($model) . "',
                            `api_custom_field` = '$store',
                            `stock_status_id` = '" . (int)$stock_status_id . "',
                            `image` = '" . $this->db->escape($image) . "',
                            `manufacturer_id` = " . $manufacturer_id . ",
                            `shipping` = '" . (int)$shipping . "',
                            `price` = '" . (float)$price . "',
                            `points` = '" . (int)$points . "', 
                            `tax_class_id` = '" . (int)$tax_class_id . "',
                            `date_available` = 'CURDATE()',
                            `weight` = " . (float)$weight . ",
                            `weight_class_id` = '" . (int)$weight_class_id . "',
                            `length` = '" . (float)$length . "',
                            `width` = '" . (float)$width . "',
                            `height` = '" . (float)$height . "',
                            `length_class_id` = '" . (int)$length_class_id . "',
                            `subtract` = $subtract,
                            `minimum` = '" . (int)$minimum . "',
                            `sort_order` = '" . (int)$sort_order . "',  
                            `status` = '" . (int)$status . "',
                            `viewed` = '" . (int)$viewed . "',
                            `date_modified` = " . $date_modified . "
                        WHERE `api_id` = '" . (string) $api_id . "'
                        ORDER BY `product_id` $order
                        LIMIT 1;";
                db_query_handler($this->db, $SQL, true);
            }
            else
            {
                usleep(rand(100000, 250000));
                //Insert - Update product table
                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product`
                        SET `model` = '" . $this->db->escape($model) . "',
                            `sku` = '" . $this->db->escape($sku) . "',
                            `upc` = '" . $this->db->escape($upc) . "',
                            `ean` = '" . $this->db->escape($ean) . "',
                            `jan` = '" . $this->db->escape($jan) . "',
                            `isbn`= '" . $this->db->escape($isbn) . "',
                            `mpn` = '" . $this->db->escape($mpn) . "',
                            `location` = '" . $this->db->escape($location) . "',
                            `quantity` = '" . (int)$quantity . "',
                            `api_id` = '" . $this->db->escape($api_id) . "',
                            `api_custom_field` = '$store',
                            `stock_status_id` = '" . (int)$stock_status_id . "',
                            `image` = '" . $this->db->escape($image) . "',
                            `manufacturer_id` = " . $manufacturer_id . ",
                            `shipping` = '" . (int)$shipping . "',
                            `price` = '" . (float)$price . "',
                            `points` = '" . (int)$points . "', 
                            `tax_class_id` = '" . (int)$tax_class_id . "',
                            `date_available` = 'CURDATE()',
                            `weight` = " . (float)$weight . ",
                            `weight_class_id` = '" . (int)$weight_class_id . "',
                            `length` = '" . (float)$length . "',
                            `width` = '" . (float)$width . "',
                            `height` = '" . (float)$height . "',
                            `length_class_id` = '" . (int)$length_class_id . "',
                            `subtract` = $subtract,
                            `minimum` = '" . (int)$minimum . "',
                            `sort_order` = '" . (int)$sort_order . "',  
                            `status` = '" . (int)$status . "',
                            `viewed` = '" . (int)$viewed . "',
                            `date_added` = NOW(),
                            `date_modified` = " . $date_modified . ";";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();
            }

            if (!$last_inserted_id)
            {
                usleep(rand(100000, 250000));
                $SQL = "SELECT `product_id`
                        FROM `" . DB_PREFIX . "product`
                        WHERE `api_id` = '" . $this->db->escape($api_id) . "'";
                $result = db_query_handler($this->db, $SQL, true);
                if (!$result->num_rows)
                {
                    continue;
                }

                $last_inserted_id = (int)$result->row['product_id'];
            }

            //Insert - Update product_description table
            //This is compatible with two languages. It needs other approach to play correctly.
            foreach ($this->language_ids as $key => $id)
            {
                usleep(rand(100000, 250000));
                $language_name = $key == 0 ? $foreign_name : $name;
                $language_description = $key == 0 ? $foreign_description : $description;
                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_description`
                        SET `product_id` = '$last_inserted_id',
                            `language_id` = '$id',
                            `name` = '" . $this->db->escape($language_name) . "',
                            `description` = '" . $this->db->escape($language_description) . "',
                            `tag` = '',
                            `meta_title` = '" . $this->db->escape($language_name) . "',
                            `meta_description` = '" . $this->db->escape($language_description) . "',
                            `meta_keyword` = ''
                       ON DUPLICATE KEY UPDATE
                            `tag` = VALUES(`tag`);";
                db_query_handler($this->db, $SQL, true);
            }

            usleep(rand(100000, 250000));
            $SQL = "SELECT `product_special_id`
                    FROM `" . DB_PREFIX . "product_special`
                    WHERE `product_id` = '" . $last_inserted_id . "'
                    LIMIT 1;";
            $result = db_query_handler($this->db, $SQL, true);
            if ($result->num_rows)
            {
                usleep(rand(100000, 250000));
                $date_end = isset($special_price) && $special_price > 0 ? '0000-00-00' : 'NOW()';
                //Update product_special table
                $SQL = "UPDATE `" . DB_PREFIX . "product_special`
                        SET `price` = '$special_price',
                            `date_end` = $date_end
                        WHERE `product_special_id` = " . (int)$last_inserted_id . ";";
                db_query_handler($this->db, $SQL, true);
            }
            else
            {
                foreach ($this->customer_group_ids as $id)
                {
                    if (isset($special_price))
                    {
                        //Insert product_special table
                        usleep(rand(100000, 250000));
                        $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_special`
                                SET `product_id` = '$last_inserted_id',
                                    `priority` = 1,
                                    `price` = '$special_price',
                                    `customer_group_id` = " . (int)$id . ",
                                    `date_start` = '0000-00-00',
                                    `date_end` = '0000-00-00';";
                        db_query_handler($this->db, $SQL, true);
                    }
                }
            }

            //Insert - Update product_to_store table
            usleep(rand(100000, 250000));
            $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_to_store`
                    SET `product_id` = '$last_inserted_id',
                        `store_id` = " . (int)$store . ";";
            db_query_handler($this->db, $SQL, true);

            //Insert - Update product_to_layout table
            usleep(rand(100000, 250000));
            $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_to_layout`
                    SET `product_id` = '$last_inserted_id',
                        `store_id` =  " . (int)$store . ",
                        `layout_id` = 0;";
            db_query_handler($this->db, $SQL, true);

            //Insert product_to_category table
            usleep(rand(100000, 250000));
            $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_to_category`
                    SET `product_id` = '$last_inserted_id',
                        `category_id` = (SELECT category_id
                                         FROM `" . DB_PREFIX . "category`
                                         WHERE `api_id` = '" . $this->db->escape($product_to_category) . "');";
            db_query_handler($this->db, $SQL, true);

            //Insert - Update product_attribute table
            if (!empty($attribute))
            {
                foreach ($attribute as $attr)
                {
                    foreach ($this->language_ids as $key => $id)
                    {
                        $language_name = $key == 0 ? $attr['attributeForeignText'] : $attr['attributeText'];
                        usleep(rand(100000, 250000));
                        $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_attribute`
                                SET `product_id` = '$last_inserted_id',
                                    `attribute_id` = (SELECT attribute_id
                                                      FROM `" . DB_PREFIX . "attribute`
                                                      WHERE `api_id` = '" . $this->db->escape($attr['itemAttributeId']) . "'),
                                    `language_id` = '$id',
                                    `text` = '" . $this->db->escape($language_name) . "'
                                ON DUPLICATE KEY UPDATE
                                    `attribute_id` = VALUES(`attribute_id`),
                                    `text` = VALUES(`text`);";
                        db_query_handler($this->db, $SQL, true);
                    }
                }
            }

            //Insert - Insert product_filter table
            foreach ($filters as $filter)
            {
                usleep(rand(100000, 250000));
                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_filter`
                        SET `product_id` = '$last_inserted_id',
                            `filter_id` = (SELECT filter_id
                                           FROM `" . DB_PREFIX . "filter`
                                           WHERE `api_id` = '" . $this->db->escape($filter['filterId']) . "');";
                db_query_handler($this->db, $SQL, true);
            }

            //Insert - Update product_option and product_option_value tables
            foreach ($options as $option_key => $option)
            {
                $last_inserted_product_option_id = 0;
                $sku = $option['itemAlterCode'];
                $quantity = $option['quantity'] ?? 0;
                foreach ($option['option'] as $option_val)
                {
                    if (!isset($option_val['optionId']))
                    {
                        continue;
                    }
                    
                    usleep(rand(100000, 250000));
                    $SQL = "SELECT `product_option_id`
                            FROM `" . DB_PREFIX . "product_option`
                            WHERE `product_id` = '$last_inserted_id' 
                                AND `option_id` = (
                                    SELECT option_id
                                    FROM `" . DB_PREFIX . "option`
                                    WHERE `api_id` = '" . $this->db->escape($option_val['optionId']) . "');";
                    $result = db_query_handler($this->db, $SQL, true);
                    if (!$result->num_rows)
                    {
                        usleep(rand(100000, 250000));
                        $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_option`
                                SET `product_id` = '$last_inserted_id',
                                    `option_id` = (
                                        SELECT option_id
                                        FROM `" . DB_PREFIX . "option`
                                        WHERE `api_id` = '" . $this->db->escape($option_val['optionId']) . "'),
                                    `value` = '',
                                    `required` = 1;";
                        db_query_handler($this->db, $SQL, true);
                        $last_inserted_product_option_id = (int)$this->db->getLastId();
                    }

                    $last_inserted_product_option_id = !$last_inserted_product_option_id ? $result->row['product_option_id'] : $last_inserted_product_option_id;
                    $option_value_api_key = $option_val['optionValueId'];
                    usleep(rand(100000, 250000));
                    $SQL = "SELECT `product_option_value_id`, `api_filter_id`
                            FROM `" . DB_PREFIX . "product_option_value`
                            WHERE `product_option_id` = '" . (int)$last_inserted_product_option_id . "' AND `product_id` = '$last_inserted_id' 
                                AND `option_id` = (
                                    SELECT option_id
                                    FROM `" . DB_PREFIX . "option`
                                    WHERE `api_id` = '" . $this->db->escape($option_val['optionId']) . "')
                                AND `option_value_id` = (SELECT option_value_id
                                                         FROM `" . DB_PREFIX . "option_value`
                                                         WHERE `api_id` = '" . $this->db->escape($option_value_api_key) . "');";
                    $result = db_query_handler($this->db, $SQL, true);
                    if ($result->num_rows)
                    {
                        usleep(rand(100000, 250000));
                        $SQL = "UPDATE `" . DB_PREFIX . "product_option_value`
                                SET `api_filter_id` = (SELECT api_filter_id
                                                       FROM `" . DB_PREFIX . "option_value`
                                                       WHERE `api_id` = '" . $this->db->escape($option_value_api_key) . "'
                                                       ),
                                    `subtract` = '" . $this->db->escape($subtract) . "',
                                    `weight` = " . (float)$weight . "
                                WHERE `product_option_value_id` = " . (int)$result->row['product_option_value_id'] . ";";
                        db_query_handler($this->db, $SQL, true);
                    }
                    else
                    {
                        usleep(rand(100000, 250000));
                        $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_option_value`
                                SET `product_option_id` = '$last_inserted_product_option_id',
                                    `product_id` = '$last_inserted_id',
                                    `option_id` = (SELECT option_id
                                                   FROM `" . DB_PREFIX . "option`
                                                   WHERE `api_id` = '" . $this->db->escape($option_val['optionId']) . "'),
                                    `option_value_id` = (SELECT option_value_id
                                                         FROM `" . DB_PREFIX . "option_value`
                                                         WHERE `api_id` = '" . $this->db->escape($option_value_api_key) . "'),
                                    `quantity` = '$quantity',
                                    `api_filter_id` = (SELECT `F`.`filter_id`
                                                       FROM `" . DB_PREFIX . "filter` F
                                                       INNER JOIN `" . DB_PREFIX . "option_value` OV
                                                       WHERE `OV`.`api_id` = '" . $this->db->escape($option_value_api_key) . "'),
                                    `subtract` = '" . $this->db->escape($subtract) . "',
                                    `price` = 0,
                                    `price_prefix` = '',
                                    `points` = 0,
                                    `points_prefix` = '+',
                                    `weight` = " . (float)$weight . ",
                                    `weight_prefix` = '+';";
                        db_query_handler($this->db, $SQL, true);
                    }
                }
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'Update products operation failed.');
            exit();
        }
    }

    public function integrateProductImage($store = 0): void
    {
        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateProductImage');
            exit();
        }

        if (is_dir(IMAGES))
        {
            $files = new RecursiveDirectoryIterator(IMAGES, RecursiveDirectoryIterator::SKIP_DOTS);
            foreach (new RecursiveIteratorIterator($files) as $index => $file)
            {
                $output_array = array();
                $path = $file->getPathname();
                $filename = $file->getFilename();
                if (preg_match("/[$]DETAIL[0-9]/i", $filename, $output_array))
                {
                    /*
                     * Table `product_image`
                     * If product image already exists, skip.
                     * If product image is new, insert.
                     */
                    $model_and_colour = str_replace('$', '/', preg_split('/[$]DETAIL/i', $filename)[0]);
                    $sort = trim($output_array[0], '$DETAIL');
                    $exploded_path = explode('/image/', $path)[1];
                    usleep(rand(100000, 250000));
                    $SQL = "SELECT `product_id`
                            FROM `" . DB_PREFIX . "product`
                            WHERE INSTR('" . $this->db->escape($model_and_colour) . "', `model`) AND `api_custom_field` = " . (int)$store . "
                            LIMIT 1;";
                    $result = db_query_handler($this->db, $SQL, true);
                    $product_id = $result->row['product_id'] ?? 0;
                    if (!$product_id)
                    {
                        continue;
                    }

                    $condition = "AND `product_id` = '$product_id' AND
                                 `api_id` IS NULL";
                    if ($row = check_existence($this->db, DB_PREFIX . 'product_image', 'image', $this->db->escape($exploded_path), 'product_image_id', $condition))
                    {
                        usleep(rand(100000, 250000));
                        $SQL = "UPDATE `" . DB_PREFIX . "product_image`
                                SET `api_id` = '" . $this->db->escape($exploded_path) . "'
                                WHERE `image` = '" . $this->db->escape($exploded_path) . "' $condition;";
                        db_query_handler($this->db, $SQL, true);
                        continue;
                    }

                    usleep(rand(100000, 250000));
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_image` 
                            SET `product_id` = '$product_id',
                                `image` = '" . $this->db->escape($exploded_path) . "',
                                `api_id` = '" . $this->db->escape($exploded_path) . "',
                                `sort_order` = '" . (int)$sort . "';";
                    db_query_handler($this->db, $SQL, true);
                }
            }

            $transaction_commit = $this->db->commitTransaction();
            if (!$transaction_commit)
            {
                log_error('[Failed Transaction]', 'integrateRelatedOptionImage');
                exit();
            }
        }
    }

    public function integrateProductDescription(): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on product update description.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateProductDescription');
            exit();
        }

        $SQL = null;
        foreach ($data as $value)
        {
            $api_id = $value['itemID'] >> null;
            $description = $value['itemDescription'] ?? null;
            $foreign_description = $value['itemForeignDescription'] ?? null;
            if (!isset($api_id) || !isset($description))
            {
                continue;
            }

            /*
             * Table `product_description`
             * Update product_description table.
             */
            foreach ($this->language_ids as $key => $id)
            {

                $language_description = $key == 0 ? $foreign_description : $description;
                usleep(rand(100000, 250000));
                $SQL = "UPDATE `" . DB_PREFIX . "product_description`
                        SET `description` = '" . $this->db->escape($language_description) . "'
                        WHERE `product_id` = (SELECT `product_id`
                                              FROM `" . DB_PREFIX . "product`
                                              WHERE `api_id` = '" . $this->db->escape($api_id) . "') AND language_id = " . (int)$id . ";";
                db_query_handler($this->db, $SQL, true);
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'integrateProductDescription');
            exit();
        }
    }

    /*
     * Data structure.
     * $data = array(
        "product apiId" => array(
            "options" => array(
                "options" => array(
                    "optionColourApiId" => array("optionValueApiId" => "option value quantity"),
                    "optionSizeApiId" => array("optionValueApiId" => "option value quantity")
                ),
                "quantity" => "Product quantity"
            )
        )
       );
    */
    public function integrateProductsQuantityAndOptions($data = array()): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on product update quantity and options.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateProductsQuantityAndOptions');
            exit();
        }

        $SQL = null;
        foreach ($data as $product_api_id => $value)
        {
            if (!isset($product_api_id))
            {
                continue;
            }

            $quantity = $value['quantity'] ?? 0;
            $stock_status_id = $quantity > 0 ? '7' : '5';
            $status = $quantity > 0 ? 1 : 0;
            if ($status)
            {
                usleep(rand(100000, 250000));
                $SQL = "SELECT `image`
                        FROM `" . DB_PREFIX . "product`
                        WHERE  `api_id` = '" . $this->db->escape($product_api_id) . "';";
                $result = db_query_handler($this->db, $SQL, true);
                if ($result->num_rows)
                {
                    $image = $result->row['image'];
                    $status = file_exists(DIR_IMAGE . $image) && $image != 'no_image.png' ? $status : 0;
                }
            }

            /*
             * Table `product`
             * Update product table quantity.
             */
            $SQL = "UPDATE `" . DB_PREFIX . "product`
                    SET `quantity` = " . (int)$quantity . ",
                        `stock_status_id` = " . (int)$stock_status_id . ",
                        `status` = " . (int)$status . "
                    WHERE `api_id` = '" . $this->db->escape($product_api_id) . "';";
            db_query_handler($this->db, $SQL, true);

            foreach ($value['options'] as $option_api_id => $option_value)
            {
                foreach ($option_value as $option_value_api_id => $quantity)
                {
                    /*
                     * Table `product_option_value`
                     * Update product_option_value table quantity.
                     */
                    usleep(rand(100000, 250000));
                    $SQL = "UPDATE `" . DB_PREFIX . "product_option_value`
                            SET `quantity` = " . (int)$quantity . "
                            WHERE `product_id` = (SELECT `product_id`
                                                FROM `" . DB_PREFIX . "product`
                                                WHERE `api_id` = '" . $this->db->escape($product_api_id) . "') AND
                                `option_id` = (SELECT option_id
                                                FROM `" . DB_PREFIX . "option`
                                                WHERE `api_id` = '" . $this->db->escape($option_api_id) . "') AND
                                `option_value_id` = (SELECT option_value_id
                                                     FROM `" . DB_PREFIX . "option_value`
                                                     WHERE `api_id` = '" . $this->db->escape($option_value_api_id) . "');";
                    db_query_handler($this->db, $SQL, true);
                }
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'integrateProductsQuantityAndOptions');
            exit();
        }
    }

    public function productsNewArrival($store = 0): void
    {
        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'productsNewArrival');
            exit();
        }

        $SQL = "SELECT `product_id`
                FROM `" . DB_PREFIX . "product`
                WHERE `api_custom_field` = " . (int)$store . " AND `date_added` > DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = db_query_handler($this->db, $SQL, true);
        foreach ($result->rows as $row)
        {
            $product_id = $row['product_id'];
            $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "product_to_category`
                    SET `product_id` = " . (int)$product_id . ",
                        `category_id` = (SELECT category_id
                                         FROM `" . DB_PREFIX . "category_description`
                                         WHERE `name` = 'Νεες Αφίξεις'
                                         LIMIT 1);";
            db_query_handler($this->db, $SQL, true);
        }

        $SQL = "SELECT `product_id`
                FROM `" . DB_PREFIX . "product`
                WHERE `api_custom_field` = " . (int)$store . " AND `date_added` < DATE_SUB(NOW(), INTERVAL 30 DAY)";
        $result = db_query_handler($this->db, $SQL, true);
        foreach ($result->rows as $row)
        {
            $product_id = $row['product_id'];
            usleep(rand(100000, 250000));
            $SQL = "DELETE 
                    FROM `" . DB_PREFIX . "product_to_category`
                    WHERE `product_id` = " . (int)$product_id . " 
                        AND `category_id` = (SELECT category_id
                                             FROM `" . DB_PREFIX . "category_description`
                                             WHERE `name` = 'Νεες Αφίξεις'
                                             LIMIT 1);";
            db_query_handler($this->db, $SQL, true);
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'productsNewArrival');
            exit();
        }
    }

    public function updateEshopActiveProducts($store, $data = array()): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on active product update.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'updateEshopActiveProducts');
            exit();
        }

        $SQL = null;
        usleep(rand(100000, 250000));
        $SQL = "UPDATE `" . DB_PREFIX . "product`
                SET `status` = 0
                WHERE `api_custom_field` = " . (int)$store . " AND api_id NOT IN ($data[0])";
        db_query_handler($this->db, $SQL, true);

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'updateEshopActiveProducts');
            exit();
        }
    }
}
