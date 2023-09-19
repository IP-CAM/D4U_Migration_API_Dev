<?php

class ModelExtensionApi4uAttribute extends Model
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
        "attributeGroupID" => "ERP attribute group id",
        "attributeGroupName" => "Attribute group name",
        "attributeGroupForeignName" => "Attribute group foreign name",
       );
     */
    public function integrateAttributeGroup($data = array()): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on attribute group.');
            return;
        }

        //Begin transaction
        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateAttributeGroup');
            exit();
        }

        //Iterate through group attributes
        foreach ($data as $attribute_group_array)
        {
            if (!isset($attribute_group_array['attributeGroupName']) || !isset($attribute_group_array['attributeGroupID']))
            {
                continue;
            }

            /*
             * Tables `attribute_group`, `attribute_group_description`
             * If attribute already exists, skip.
             * If attribute is new, insert.
             * If attribute description changed, update.
             */
            $SQL = "SELECT `AG`.`attribute_group_id`
                    FROM `" . DB_PREFIX . "attribute_group` AG
                    INNER JOIN `" . DB_PREFIX . "attribute_group_description` AGD ON `AGD`.`attribute_group_id` = `AG`.`attribute_group_id`
                    WHERE `AGD`.`name` = '" . $this->db->escape($attribute_group_array['attributeGroupName']) . "' AND `AG`.`api_id` = '" . $this->db->escape($attribute_group_array['attributeGroupID']) . "';";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {
                if ($row = check_existence($this->db, DB_PREFIX . 'attribute_group_description', 'name', $attribute_group_array['attributeGroupName'], 'attribute_group_id'))
                {
                    $SQL = "UPDATE `" . DB_PREFIX . "attribute_group`
                            SET `api_id` = '" . $this->db->escape($attribute_group_array['attributeGroupID']) . "'
                            WHERE `attribute_group_id` = '" . (int)$row['attribute_group_id'] . "';";
                    db_query_handler($this->db, $SQL, true);
                    continue;
                }

                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "attribute_group` 
                        SET `api_id` = '" . $this->db->escape($attribute_group_array['attributeGroupID']) . "',
                            `sort_order` = 0;";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();
                if (!$last_inserted_id)
                {
                    $SQL = "SELECT `AG`.`attribute_group_id`
                            FROM " . DB_PREFIX . "attribute_group AG
                            WHERE `AG`.`api_id` = '{$attribute_group_array['attributeGroupID']}'";
                    $result = db_query_handler($this->db, $SQL, true);
                    $last_inserted_id = $result->row['attribute_group_id'];
                }

                foreach ($this->language_ids as $key => $id)
                {
                    $foreign_name = isset($attribute_group_array['attributeGroupForeignName']) ? $attribute_group_array['attributeGroupForeignName'] : $attribute_group_array['attributeName'];
                    $language_name = $key == 0 ? $foreign_name : $attribute_group_array['attributeGroupName'];
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "attribute_group_description`
                            SET `attribute_group_id` = '$last_inserted_id',
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
            log_error('[Failed Transaction Commit]', 'integrateAttributeGroup');
            exit();
        }
    }

    /*
     * Data structure.
     * $data = array(
        "attributeID" => "ERP attribute id",
        "attributeGroupID" => 'ERP attribute group id',
        "attributeName" => "Attribute name",
        "attributeForeignName" => "Attribute foreign name",
       );
     */
    public function integrateAttribute($data = array()): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on attribute.');
            return;
        }

        //Begin transaction
        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateAttribute');
            exit();
        }

        //Iterate through attributes
        foreach ($data as $attribute_array)
        {
            if (!isset($attribute_array['attributeID']) || !isset($attribute_array['attributeName']) || !isset($attribute_array['attributeGroupID']))
            {
                continue;
            }

            /*
             * Tables `attribute`, `attribute_description`
             * If attribute already exists, skip.
             * If attribute is new, insert.
             * If attribute description changed, update.
            */
            $attribute_group_id = null;
            $SQL = "SELECT `attribute_group_id`
                    FROM `" . DB_PREFIX . "attribute_group`
                    WHERE `api_id` = '" . $this->db->escape($attribute_array['attributeGroupID']) . "'";
            $result = db_query_handler($this->db, $SQL, true);
            if ($result->num_rows)
            {
                $attribute_group_id = (int)$result->row['attribute_group_id'];
            }

            $SQL = "SELECT `A`.`attribute_id`
                    FROM `" . DB_PREFIX . "attribute` A
                    INNER JOIN `" . DB_PREFIX . "attribute_description` AD ON `AD`.`attribute_id` = `A`.`attribute_id`
                    WHERE `AD`.`name` = '" . $this->db->escape($attribute_array['attributeName']) . "' AND `A`.`attribute_group_id` = '$attribute_group_id' AND `A`.`api_id` = '" . $this->db->escape($attribute_array['attributeID']) . "';";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {
                if ($row = check_existence($this->db, DB_PREFIX . 'attribute_description', 'name', $attribute_array['attributeName'], 'attribute_id'))
                {
                    $SQL = "UPDATE `" . DB_PREFIX . "attribute`
                            SET `api_id` = '" . $this->db->escape($attribute_array['attributeID']) . "'
                            WHERE `attribute_id` = '" . (int)$row['attribute_id'] . "';";
                    db_query_handler($this->db, $SQL, true);
                    continue;
                }

                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "attribute` 
                        SET `attribute_group_id` = '$attribute_group_id',
                            `api_id` = '" . $this->db->escape($attribute_array['attributeID']) . "',
                            `sort_order` = 0;";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();
                if (!$last_inserted_id)
                {
                    $SQL = "SELECT `A`.`attribute_id`
                            FROM `" . DB_PREFIX . "attribute` A
                            WHERE `A`.`api_id` = '{$attribute_array['attributeID']}'";
                    $result = db_query_handler($this->db, $SQL, true);
                    $last_inserted_id = $result->row['attribute_id'];
                }

                foreach ($this->language_ids as $key => $id)
                {
                    $foreign_name = isset($attribute_array['attributeForeignName']) ? $attribute_array['attributeForeignName'] : $attribute_array['attributeName'];
                    $language_name = $key == 0 ? $foreign_name : $attribute_array['attributeName'];
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "attribute_description`
                            SET `attribute_id` = $last_inserted_id,
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
            log_error('[Failed Transaction Commit]', 'integrateAttribute');
            exit();
        }
    }
}