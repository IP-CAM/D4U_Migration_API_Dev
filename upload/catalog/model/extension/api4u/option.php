<?php

class ModelExtensionApi4uOption extends Model
{
    public $language_ids = array();
    public $clothing_size_order = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language_ids = $this->config->get('config_language_ids');
        $this->clothing_size_order = array('ONE SIZE' => 1, 'S' => 2, 'XS' => 3, 'M' => 4, 'L' => 5, 'XL' => 6, '2XL' => 7, '3XL' => 8, '4XL' => 9);
    }

    /*
     * Data structure.
     * $data = array(
        "optionId" => "ERP option id",
        "optionName" => "Option name",
        "optionForeignName" => "Option foreign name",
        "type" => "Option type",
        "sortOrder" => "Sort order"
       );
    */
    public function integrateOption($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on option.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateOption');
            exit();
        }

        foreach ($data as $option_array)
        {
            if (!isset($option_array['optionId']) || !isset($option_array['optionName']))
            {
                continue;
            }

            $option_array['type'] = 'radio';
            $option_array['sortOrder'] = 0 ;

            /*
             * Tables `option`, `option_description`
             * If option already exists, skip.
             * If option is new, insert.
             * If option description changed, update.
             */
            $sort_order = $option_array['sortOrder'] ?? 0;
            $SQL = "SELECT O.`option_id`
                    FROM `" . DB_PREFIX . "option` O 
                    INNER JOIN `" . DB_PREFIX . "option_description` OD ON OD.`option_id` = O.`option_id`
                    WHERE OD.`name` = '" . $this->db->escape($option_array['optionName']) . "' AND O.`api_id` = '" . $this->db->escape($option_array['optionId']) . "';";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {
                if ($row = check_existence($this->db, DB_PREFIX . 'option_description', 'name', $option_array['optionName'], 'option_id'))
                {
                    $SQL = "UPDATE `" . DB_PREFIX . "option`
                            SET `api_id` = '" . $this->db->escape($option_array['optionId']) . "'
                            WHERE `option_id` = '" . (int)$row['option_id'] . "';";
                    db_query_handler($this->db, $SQL, true);
                    continue;
                }

                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "option` 
                        SET `api_id` = '" . $this->db->escape($option_array['optionId']) . "',
                            `type` = '" . $this->db->escape($option_array['type']) . "',
                            `sort_order` = " . (int)$sort_order . ";";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();
                if (!$last_inserted_id)
                {
                    $SQL = "SELECT O.`option_id`
                            FROM " . DB_PREFIX . "option O
                            WHERE `O`.`api_id` = '{$option_array['optionId']}'";
                    $result = db_query_handler($this->db, $SQL, true);
                    $last_inserted_id = $result->row['category_id'];
                }

                foreach ($this->language_ids as $key => $id)
                {
                    $foreign_name = isset($option_array['optionForeignName']) ? $option_array['optionForeignName'] : $option_array['optionName'];
                    $language_name = $key == 0 ? $foreign_name : $option_array['optionName'];
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "option_description`
                            SET `option_id` = $last_inserted_id,
                                `language_id` = " . (int)$id . ",
                                `name` = '" . $this->db->escape($language_name) . "'
                            ON DUPLICATE KEY UPDATE
                                `name` = VALUES(`name`);";
                    db_query_handler($this->db, $SQL, true);
                }
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'integrateOption');
            exit();
        }
    }

    /*
     * Data structure.
     * $data = array(
        "optionValueId" => "ERP option id",
        "optionId" => "optionId",
        "filterId" => "Parent filter of option"
        "optionValueName" => "Option value name",
        "sortOrder" => "Sort order",
     );
     */
    public function integrateOptionValue($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on option value.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateOptionValue');
            exit();
        }

        //Iterate through option values
        foreach ($data as $option_value_array)
        {
            if (!isset($option_value_array['optionValueId']) || !isset($option_value_array['optionValueName']) || !isset($option_value_array['optionId']))
            {
                continue;
            }

            $option_value_array['sortOrder'] = $this->clothing_size_order[$option_value_array['optionValueName']] ?? 0 ;

            /*
             * Tables `option_value`, `option_value_description`
             * If option value already exists, skip.
             * If option value is new, insert.
             */
            $option_id = null;
            $SQL = "SELECT `option_id`
                    FROM `" . DB_PREFIX . "option`
                    WHERE `api_id` = '" . $this->db->escape($option_value_array['optionId']) . "'";
            $result = db_query_handler($this->db, $SQL, true);
            if ($result->num_rows)
            {
                $option_id = (int)$result->row['option_id'];
            }

            $SQL = "SELECT `OV`.`option_value_id`
                    FROM `" . DB_PREFIX . "option_value` OV
                    INNER JOIN `" . DB_PREFIX . "option_value_description` OVD ON `OVD`.`option_value_id` = `OV`.`option_value_id`
                    WHERE `OVD`.`name` = '" . $this->db->escape($option_value_array['optionValueName']) . "' AND `OV`.`option_id` = '$option_id' AND `OV`.`api_id` = '" . $this->db->escape($option_value_array['optionValueId']) . "';";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {
                $filter_api_key = $option_value_array['filterId'] ?? '';
                if ($row = check_existence($this->db, DB_PREFIX . 'option_value_description', 'name', $option_value_array['optionValueName'], 'option_value_id'))
                {
                    $SQL = "UPDATE `" . DB_PREFIX . "option_value`
                            SET `api_id` = '" . $this->db->escape($option_value_array['optionValueId']) . "',
                                `api_filter_id` = (SELECT `F`.`filter_id`
                                               FROM `" . DB_PREFIX . "filter` F
                                               WHERE `api_id` = '" . $this->db->escape($filter_api_key) . "')
                            WHERE `option_value_id` = '" . (int)$row['option_value_id'] . "';";
                    db_query_handler($this->db, $SQL, true);
                    continue;
                }

                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "option_value` 
                        SET `api_id` = '" . $this->db->escape($option_value_array['optionValueId']) . "',
                            `option_id` = $option_id,
                            `api_filter_id` = (SELECT `F`.`filter_id`
                                               FROM `" . DB_PREFIX . "filter` F
                                               WHERE `api_id` = '" . $this->db->escape($filter_api_key) . "'),
                            `sort_order` = " . (int)$option_value_array['sortOrder'] . "
                        ON DUPLICATE KEY UPDATE
                            `api_filter_id` = VALUES(`api_filter_id`);";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();

                foreach ($this->language_ids as $key => $id)
                {
                    $foreign_name = isset($option_value_array['optionValueForeignName']) ? $option_value_array['optionValueForeignName'] : $option_value_array['optionValueName'];
                    $language_name = $key == 0 ? $foreign_name : $option_value_array['optionValueName'];
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "option_value_description`
                            SET `option_value_id` = $last_inserted_id,
                                `language_id` = " . (int)$id . ",
                                `option_id` = $option_id,
                                `name` = '" . $this->db->escape($language_name) . "'
                            ON DUPLICATE KEY UPDATE
                                `name` = VALUES(`name`);";
                    db_query_handler($this->db, $SQL, true);
                }
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction]', 'integrateOptionValue');
            exit();
        }
    }
}