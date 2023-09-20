<?php

class ModelExtensionApi4uFilter extends Model
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
        "filterGroupID" => "ERP filter group id",
        "filterGroupName" => "Filter group name",
        "filterGroupForeignName" => "Filter group foreign name",
       );
     */
    public function integrateFilterGroup($data = array())
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on filter group.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateFilterGroup');
            $this->db->close();
            exit();
        }

        //Iterate through group filters
        foreach ($data as $filter_group_array)
        {
            if (!isset($filter_group_array['filterGroupID']) || !isset($filter_group_array['filterGroupName']))
            {
                continue;
            }

            /*
             * Tables `filter_group`, `filter_group_description`
             * If filter group already exists, skip.
             * If filter group is new, insert.
             * If filter group description changed, update.
             */
            usleep(rand(30000, 100000));
$SQL = "SELECT `FG`.`filter_group_id`
                    FROM `" . DB_PREFIX . "filter_group` FG
                    INNER JOIN `" . DB_PREFIX . "filter_group_description` FGD ON `FGD`.`filter_group_id` = `FG`.`filter_group_id`
                    WHERE `FGD`.`name` = '" . $this->db->escape($filter_group_array['filterGroupName']) . "' AND `FG`.`api_id` = '" . $this->db->escape($filter_group_array['filterGroupID']) . "';";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {
                if ($row = check_existence($this->db, DB_PREFIX . 'filter_group_description', 'name', $filter_group_array['filterGroupName'], 'filter_group_id'))
                {
                    usleep(rand(30000, 100000));
$SQL = "UPDATE `" . DB_PREFIX . "filter_group`
                            SET `api_id` = '" . $this->db->escape($filter_group_array['filterGroupID']) . "'
                            WHERE `filter_group_id` = '" . (int)$row['filter_group_id'] . "';";
                    db_query_handler($this->db, $SQL, true);
                    continue;
                }

                usleep(rand(30000, 100000));
$SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "filter_group` 
                        SET `api_id` = '" . $this->db->escape($filter_group_array['filterGroupID']) . "',
                            `sort_order` = 0;";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();
                if (!$last_inserted_id)
                {
                    usleep(rand(30000, 100000));
$SQL = "SELECT `FG`.`filter_group_id`
                            FROM " . DB_PREFIX . "filter_group FG
                            WHERE `FG`.`api_id` = '{$filter_group_array['filterGroupID']}'";
                    $result = db_query_handler($this->db, $SQL, true);
                    $last_inserted_id = $result->row['filter_group_id'];
                }

                foreach ($this->language_ids as $key => $id)
                {
                    $foreign_name = isset($filter_group_array['filterGroupForeignName']) ? $filter_group_array['filterGroupForeignName'] : $filter_group_array['filterGroupName'];
                    $language_name = $key == 0 ? $foreign_name : $filter_group_array['filterGroupName'];
                    usleep(rand(30000, 100000));
$SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "filter_group_description`
                            SET `filter_group_id` = '$last_inserted_id',
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
            log_error('[Failed Transaction Commit]', 'integrateFilterGroup');
            $this->db->close();
            exit();
        }
    }

    /*
     * Data structure.
     * $data = array(
        "filterID" => "ERP filter id",
        "filterGroupID" => 'ERP filter group id',
        "filterName" => "Filter name",
        "filterForeignName" => "Filter foreign name",
       );
     */
    public function integrateFilter($data = array())
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on filter.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateFilter');
            $this->db->close();
            exit();
        }

        //Iterate through group filters
        foreach ($data as $filter_array)
        {
            if (!isset($filter_array['filterID']) || !isset($filter_array['filterName']) || !isset($filter_array['filterGroupID']))
            {
                continue;
            }

            /*
             * Tables `filter`, `filter_description`
             * If filter already exists, skip.
             * If filter is new, insert.
             * If filter description changed, update.
             */
            $filter_group_id = null;
            usleep(rand(30000, 100000));
$SQL = "SELECT `filter_group_id`
                    FROM `" . DB_PREFIX . "filter_group`
                    WHERE `api_id` = '" . $this->db->escape($filter_array['filterGroupID']) . "'";
            $result = db_query_handler($this->db, $SQL, true);
            if ($result->num_rows)
            {
                $filter_group_id = (int)$result->row['filter_group_id'];
            }

            usleep(rand(30000, 100000));
$SQL = "SELECT `F`.`filter_id`
                    FROM `" . DB_PREFIX . "filter` F
                    INNER JOIN `" . DB_PREFIX . "filter_description` FD ON `FD`.`filter_id` = `F`.`filter_id`
                    WHERE `FD`.`name` = '" . $this->db->escape($filter_array['filterName']) . "' AND `F`.`filter_group_id` = '$filter_group_id';";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {
                usleep(rand(30000, 100000));
$SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "filter` 
                        SET `api_id` = '" . $this->db->escape($filter_array['filterID']) . "',
                            `filter_group_id` = '$filter_group_id',
                            `sort_order` = 0;";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();
                if (!$last_inserted_id)
                {
                    usleep(rand(30000, 100000));
$SQL = "SELECT `F`.`filter_id`
                            FROM `" . DB_PREFIX . "attribute` F
                            WHERE `F`.`api_id` = '{$filter_array['filterID']}'";
                    $result = db_query_handler($this->db, $SQL, true);
                    $last_inserted_id = $result->row['filter_id'];
                }

                foreach ($this->language_ids as $key => $id)
                {
                    $foreign_name = isset($filter_array['filterForeignName']) ? $filter_array['filterForeignName'] : $filter_array['filterName'];
                    $language_name = $key == 0 ? $foreign_name : $filter_array['filterName'];
                    usleep(rand(30000, 100000));
$SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "filter_description`
                            SET `filter_id` = $last_inserted_id,
                                `filter_group_id` = '$filter_group_id',
                                `language_id` = " . (int)$id . ",
                                `name` = '" . $this->db->escape($language_name) . "'
                            ON DUPLICATE KEY UPDATE
                                `name` = VALUES(`name`);";
                    db_query_handler($this->db, $SQL, true);
                }
            }
            else
            {
                usleep(rand(30000, 100000));
$SQL = "UPDATE `" . DB_PREFIX . "filter`
                        SET `api_id` = '" . $this->db->escape($filter_array['filterID']) . "'
                        WHERE `filter_id` = '" . (int)$result->row['filter_id'] . "';";
                db_query_handler($this->db, $SQL, true);
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction Commit]', 'integrateFilter');
            $this->db->close();
            exit();
        }
    }
}