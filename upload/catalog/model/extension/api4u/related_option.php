<?php

class ModelExtensionApi4uRelatedOption extends Model
{
    public $language_ids = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language_ids = $this->config->get('config_language_ids');
    }

    /*
     * Data structure.
     * $data = array(
        "apiId" => "ERP product id",
        "color" => "Product special color name",
        "image" => "Image name"
       );
    */
    public function integrateRelatedOptionImage($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on related option image.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateRelatedOptionImage');
            exit();
        }

        foreach ($data as $related_option_array)
        {
            $api_id = $related_option_array['apiId'] ?? null;
            $colour = $related_option_array['colour'] ?? null;
            $image = $related_option_array['image'] ?? 'no_image.png';

            if (!isset($api_id) || !isset($colour) || !isset($image))
            {
                continue;
            }

            /*
             * Table `poip_option_image`
             * If related option image already exists, skip.
             * If related option image is new, insert.
             */
            $condition = "AND `product_id` = (SELECT `product_id`
                                              FROM `" . DB_PREFIX . "product`
                                              WHERE `api_id` = '" . $this->db->escape($api_id) . "') AND
                        `product_option_id` = (SELECT PP.`product_option_id`
                                               FROM `" . DB_PREFIX . "product_option` PP
                                               INNER JOIN `" . DB_PREFIX . "product` P ON P.`product_id` = PP.`product_id`
                                               INNER JOIN `" . DB_PREFIX . "option` O ON O.`option_id` = PP.`option_id`
                                               INNER JOIN `" . DB_PREFIX . "option_description` OD ON `OD`.`option_id` = `O`.`option_id`
                                               WHERE P.api_id = '" . $this->db->escape($api_id) . "' AND `OD`.`name` = 'Colour') AND
                        `product_option_value_id` = (SELECT `POV`.`product_option_value_id`
                                                     FROM `" . DB_PREFIX . "product_option_value` POV
                                                     INNER JOIN `" . DB_PREFIX . "product` P ON `P`.`product_id` = `POV`.`product_id`
                                                     INNER JOIN `" . DB_PREFIX . "option` O ON `O`.`option_id` = `POV`.`option_id`
                                                     INNER JOIN `" . DB_PREFIX . "option_value_description` OVP ON `OVP`.`option_value_id` = `POV`.`option_value_id`
                                                     INNER JOIN `" . DB_PREFIX . "product_option` PO ON `PO`.`product_option_id` = `POV`.`product_option_id`
                                                     WHERE `P`.`api_id` = '" . $this->db->escape($api_id) . "' AND `OVP`.`name` =  '" . $this->db->escape($colour) . "'
                                                     GROUP BY  `POV`.`product_option_value_id`) AND
                        `api_id` IS NULL";
            if ($row = check_existence($this->db, DB_PREFIX . 'poip_option_image', 'image', $this->db->escape($image), 'product_id', $condition))
            {
                $SQL = "UPDATE `" . DB_PREFIX . "poip_option_image`
                        SET `api_id` = '" . $this->db->escape($api_id . $colour) . "'
                        WHERE `image` = '" . $this->db->escape($image) . "' $condition;";
                db_query_handler($this->db, $SQL, true);
                continue;
            }

            $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "poip_option_image` 
                    SET `product_id` = (SELECT `product_id`
                                        FROM `" . DB_PREFIX . "product`
                                        WHERE `api_id` = '" . $this->db->escape($api_id) . "'),
                        `product_option_id` = (SELECT PP.`product_option_id`
                                               FROM `" . DB_PREFIX . "product_option` PP
                                               INNER JOIN `" . DB_PREFIX . "product` P ON P.`product_id` = PP.`product_id`
                                               INNER JOIN `" . DB_PREFIX . "option` O ON O.`option_id` = PP.`option_id`
                                               INNER JOIN `" . DB_PREFIX . "option_description` OD ON `OD`.`option_id` = `O`.`option_id`
                                               WHERE P.api_id = '" . $this->db->escape($api_id) . "' AND `OD`.`name` = 'Colour'),
                        `product_option_value_id` = (SELECT `POV`.`product_option_value_id`
                                                     FROM `" . DB_PREFIX . "product_option_value` POV
                                                     INNER JOIN `" . DB_PREFIX . "product` P ON `P`.`product_id` = `POV`.`product_id`
                                                     INNER JOIN `" . DB_PREFIX . "option` O ON `O`.`option_id` = `POV`.`option_id`
                                                     INNER JOIN `" . DB_PREFIX . "option_value_description` OVP ON `OVP`.`option_value_id` = `POV`.`option_value_id`
                                                     INNER JOIN `" . DB_PREFIX . "product_option` PO ON `PO`.`product_option_id` = `POV`.`product_option_id`
                                                     WHERE `P`.`api_id` = '" . $this->db->escape($api_id) . "' AND `OVP`.`name` =  '" . $this->db->escape($colour) . "'
                                                     GROUP BY  `POV`.`product_option_value_id`),
                        `image` = '" . $this->db->escape($image) . "',
                        `sort_order` = 1,
                        `api_id` = '" . $this->db->escape($api_id . $colour) . "',
                        `relatedoptions_id` = 0
                    ON DUPLICATE KEY UPDATE
                        `image` = VALUES(`image`);";
            db_query_handler($this->db, $SQL, true);
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'integrateRelatedOptionImage');
            exit();
        }
    }

    public function integrateRelatedOptionImageFiles($store = 0): void
    {
        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateRelatedOptionImageFiles.');
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
                    $product_id = null;
                    $api_id = null;
                    $product_option_value_id = 0;
                    $model_and_colour = str_replace('$', '/', preg_split('/[$]DETAIL/i', $filename)[0]);
                    $sort = trim($output_array[0], '$DETAIL');
                    $exploded_path = explode('/image/', $path)[1];
                    $SQL = "SELECT `product_id`, `model`, `api_id`
                            FROM `" . DB_PREFIX . "product`
                            WHERE INSTR('" . $this->db->escape($model_and_colour) . "', `model`) AND `api_custom_field` = " . (int)$store . ";";
                    $result = db_query_handler($this->db, $SQL, true);
                    if ($row = $result->row)
                    {
                        $model = $row['model'];
                        $product_id = $row['product_id'];
                        $api_id = $row['api_id'];
                    }

                    if (!isset($model) || !isset($product_id))
                    {
                        continue;
                    }

                    /*
                     * Table `poip_option_image`
                     * If related option image already exists, skip.
                     * If related option image is new, insert.
                     */
                    $colour = str_replace($model . '/', '', $model_and_colour);
                    $SQL = "SELECT `POV`.`product_option_value_id`
                            FROM `" . DB_PREFIX . "product_option_value` POV
                            INNER JOIN " . DB_PREFIX . "option_value_description OVD ON `OVD`.`option_value_id` = `POV`.`option_value_id`
                            WHERE `POV`.`product_id` = " . (int)$product_id . " AND REPLACE(`OVD`.`name`, \"/\", \" \") = REPLACE('" . $this->db->escape($colour) . "', \"/\", \" \")
                            LIMIT 1;";
                    $result = db_query_handler($this->db, $SQL, true);
                    if ($row = $result->row)
                    {
                        $product_option_value_id = $row['product_option_value_id'];
                    }

                    if (!$product_option_value_id)
                    {
                        continue;
                    }

                    if ($exploded_path == 'catalog/product-upload/24NG.SD97$1B$DENIM$BLUE$DETAIL2.jpg')
                    {
                        $test = 1;
                    }

                    $condition = "AND `product_id` = " . (int)$product_id . " AND
                                      `product_option_id` = (SELECT `PO`.`product_option_id` 
                                                             FROM `nvgrntbl_product_option` PO
                                                             INNER JOIN `nvgrntbl_option` O ON `O`.`option_id` = `PO`.`option_id`
                                                             INNER JOIN `nvgrntbl_option_description` OD ON `OD`.`option_id` = `O`.`option_id`
                                                             WHERE `PO`.`product_id` = '$product_id' AND `OD`.`name` = 'Colour') AND
                                      `product_option_value_id` = '$product_option_value_id' AND
                                      `api_id` IS NULL";

                    if ($row = check_existence($this->db, DB_PREFIX . 'poip_option_image', 'image', $this->db->escape($exploded_path), 'product_id', $condition))
                    {
                        $SQL = "UPDATE `" . DB_PREFIX . "poip_option_image`
                                SET `api_id` = '" . $this->db->escape($exploded_path) . "'
                                WHERE `image` = '" . $this->db->escape($exploded_path) . "' $condition;";
                        db_query_handler($this->db, $SQL, true);
                        continue;
                    }

                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "poip_option_image` 
                            SET `product_id` = '$product_id',
                                `product_option_id` = (SELECT `PO`.`product_option_id` 
                                                       FROM `nvgrntbl_product_option` PO
                                                       INNER JOIN `nvgrntbl_option` O ON `O`.`option_id` = `PO`.`option_id`
                                                       INNER JOIN `nvgrntbl_option_description` OD ON `OD`.`option_id` = `O`.`option_id`
                                                       WHERE `PO`.`product_id` = '$product_id' AND `OD`.`name` = 'Colour'),
                                `product_option_value_id` = '$product_option_value_id',
                                `image` = '" . $this->db->escape($exploded_path) . "',
                                `sort_order` = " . (int)$sort . ",
                                `api_id` = '" . $this->db->escape($exploded_path) . "';";
                    db_query_handler($this->db, $SQL, true);
                }
            }

            $transaction_commit = $this->db->commitTransaction();
            if (!$transaction_commit)
            {
                log_error('[Failed Transaction]', 'integrateRelatedOptionImageFiles');
                exit();
            }
        }
    }

    /*
     * Data structure.
{
  "Data": [
    {
      "itemID": "ERP product id",
      "itemAlterCode": "Product SKU",
      "options": [
        {
          "optionId": "ERP option unique id",
          "optionValueId": "ERP option value unique id"
        }
      ],
      "quantity": "SKU quantity"
    }
  ]
}
    */
    public function integrateRelatedOptionAndQuantity($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on related options.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateRelatedOptionAndQuantity');
            exit();
        }

        $variant = "color+size";
        $last_inserted_id = 0;
        usleep(rand(100000, 200000));
        $SQL = "SELECT `relatedoptions_variant_id`
                FROM `" . DB_PREFIX . "relatedoptions_variant` 
                WHERE `relatedoptions_variant_name` = '" . $this->db->escape($variant) . "';";
        $result = db_query_handler($this->db, $SQL, true);
        if (!$result->num_rows)
        {
            $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "relatedoptions_variant`
                    SET `relatedoptions_variant_name` = '" . $this->db->escape($variant) . "',
                        `sort_order` = 1;";
            db_query_handler($this->db, $SQL, true);
            $last_inserted_id = (int)$this->db->getLastId();
        }

        if (!$last_inserted_id)
        {
            $last_inserted_id = (int)$result->row['relatedoptions_variant_id'];
        }

        foreach ($data as $value)
        {
            $product_api_id = $value['itemID'];
            $stock_status_id = $value['quantity'] > 0 ? '7' : '5';
            $status = $value['quantity'] > 0 ? 1 : 0;
            $quantity = (int)$value['quantity'];
            $sku = $value['itemAlterCode'];
            foreach ($value['options'] as $variant_options)
            {
                $SQL = "SELECT `relatedoptions_variant_id`
                        FROM `" . DB_PREFIX . "relatedoptions_variant_option` 
                        WHERE `relatedoptions_variant_id` = '" . $this->db->escape($last_inserted_id) . "'
                            AND `option_id` = (SELECT option_id
                                                      FROM `" . DB_PREFIX . "option`
                                                      WHERE `api_id` = '" . $this->db->escape($variant_options['optionId']) . "');";
                $result = db_query_handler($this->db, $SQL, true);
                if (!$result->num_rows)
                {
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "relatedoptions_variant_option`
                            SET `relatedoptions_variant_id` = '" . $this->db->escape($last_inserted_id) . "',
                                `option_id` = (SELECT option_id
                                                      FROM `" . DB_PREFIX . "option`
                                                      WHERE `api_id` = '" . $this->db->escape($variant_options['optionId']) . "');";
                    db_query_handler($this->db, $SQL, true);
                }

                $SQL = "SELECT `product_ID`
                        FROM `" . DB_PREFIX . "product`
                        WHERE  `api_id` = '" . $this->db->escape($product_api_id) . "';";
                $result = db_query_handler($this->db, $SQL, true);
                if (!$result->num_rows)
                {
                    continue;
                }

                $last_inserted_relatedoptions_variant_product_id = 0;
                $last_inserted_relatedoptions_id = 0;
                $SQL = "SELECT `relatedoptions_variant_product_id`
                        FROM `" . DB_PREFIX . "relatedoptions_variant_product`
                        WHERE `relatedoptions_variant_id` = '" . $this->db->escape($last_inserted_id) . "'
                            AND `product_id` = (SELECT `product_id`
                                                FROM `" . DB_PREFIX . "product`
                                                WHERE `api_id` = '" . $this->db->escape($product_api_id) . "');";
                $result = db_query_handler($this->db, $SQL, true);
                if (!$result->num_rows)
                {
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "relatedoptions_variant_product`
                            SET `relatedoptions_variant_id` = '" . $this->db->escape($last_inserted_id) . "',
                                `product_id` = (SELECT `product_id`
                                                FROM `" . DB_PREFIX . "product`
                                                WHERE `api_id` = '" . $this->db->escape($product_api_id) . "'),
                                `relatedoptions_use` = 1,
                                `allow_zero_select` = 0;";
                    db_query_handler($this->db, $SQL, true);
                    $last_inserted_relatedoptions_variant_product_id = (int)$this->db->getLastId();
                }

                if (!$last_inserted_relatedoptions_variant_product_id)
                {
                    $last_inserted_relatedoptions_variant_product_id = (int)$result->row['relatedoptions_variant_product_id'];
                }

                $SQL = "SELECT `relatedoptions_id`
                        FROM `" . DB_PREFIX . "relatedoptions`
                        WHERE `sku` = '" . $this->db->escape($sku) . "' AND `relatedoptions_variant_product_id` = '$last_inserted_relatedoptions_variant_product_id';";
                $result = db_query_handler($this->db, $SQL, true);
                if (!$result->num_rows)
                {
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "relatedoptions`
                            SET `relatedoptions_variant_product_id` = '$last_inserted_relatedoptions_variant_product_id',
                                `product_id` = (SELECT `product_id`
                                                FROM `" . DB_PREFIX . "product`
                                                WHERE `api_id` = '" . $this->db->escape($product_api_id) . "'),
                                `quantity` = '$quantity',
                                `model` = '',
                                `sku` = '" . $this->db->escape($sku) . "',
                                `upc` = '',
                                `ean` = '',
                                `location` = '',
                                `in_stock_status_id` = '$stock_status_id',
                                `stock_status_id` = '$status',
                                `weight_prefix` = '',
                                `weight` = 0.00000000,
                                `price_prefix` = '=',
                                `price` = 0.00000000,
                                `defaultselect` = 0,
                                `defaultselectpriority` = 0,
                                `disabled` = 0.00000000;";
                    db_query_handler($this->db, $SQL, true);
                    $last_inserted_relatedoptions_id = (int)$this->db->getLastId();
                }
                else
                {
                    $last_inserted_relatedoptions_id = (int)$result->row['relatedoptions_id'];
                    $SQL = "UPDATE `" . DB_PREFIX . "relatedoptions`
                            SET `quantity` = '$quantity',
                                `in_stock_status_id` = '$stock_status_id',
                                `stock_status_id` = '$status'
                            WHERE `relatedoptions_id` = '$last_inserted_relatedoptions_id'";
                    db_query_handler($this->db, $SQL, true);
                }

                $SQL = "SELECT `relatedoptions_id`
                        FROM `" . DB_PREFIX . "relatedoptions_option` 
                        WHERE `relatedoptions_id` = '$last_inserted_relatedoptions_id'
                            AND `product_id` = (SELECT `product_id`
                                                FROM `" . DB_PREFIX . "product`
                                                WHERE `api_id` = '" . $this->db->escape($product_api_id) . "')
                            AND `option_id` = (SELECT option_id
                                               FROM `" . DB_PREFIX . "option`
                                               WHERE `api_id` = '" . $this->db->escape($variant_options['optionId']) . "')
                            AND `option_value_id` = (SELECT `option_value_id`
                                                     FROM `" . DB_PREFIX . "option_value`
                                                     WHERE `api_id` = '" . $this->db->escape($variant_options['optionValueId']) . "');";
                $result = db_query_handler($this->db, $SQL, true);
                if (!$result->num_rows)
                {
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "relatedoptions_option`
                            SET `relatedoptions_id` = '$last_inserted_relatedoptions_id',
                                `product_id` = (SELECT `product_id`
                                                FROM `" . DB_PREFIX . "product`
                                                WHERE `api_id` = '" . $this->db->escape($product_api_id) . "'),
                                `option_id` = (SELECT option_id
                                               FROM `" . DB_PREFIX . "option`
                                               WHERE `api_id` = '" . $this->db->escape($variant_options['optionId']) . "'),
                                `option_value_id` = (SELECT `option_value_id`
                                                     FROM `" . DB_PREFIX . "option_value`
                                                     WHERE `api_id` = '" . $this->db->escape($variant_options['optionValueId']) . "');";
                    db_query_handler($this->db, $SQL, true);
                }
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'integrateRelatedOptionAndQuantity');
            exit();
        }
    }
}