<?php

class ModelExtensionApi4uCategory extends Model
{
    public $language_ids = array();
    public $path_array = array();

    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->language_ids = $this->config->get('config_language_ids');
    }

    /*
     * Data structure.
     * $response['Data']['Categories'][] = array(
    "apiId" => "ERP category id",
    "ParentID" => "Category parent id or null",
    "Name" => "Category name"
    );
     */
    public function integrateCategory($data): void
    {
        if (empty($data)) {
            log_error("[API4U] Warning:", 'Empty data array on category.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction) {
            log_error('[Failed Transaction]', 'integrateCategory.');
            return;
        }

        //Iterate through categories
        foreach ($data as $category_array) {
            if (!isset($category_array['Name']) || !isset($category_array['ID'])) {
                continue;
            }

            /*
             * Tables `category`, `category_description`, `category_to_store`, `category_to_store`, `category_to_layout`
             * If category already exists, skip.
             * If category is new, insert.
             * If category description changed, update.
             */
            usleep(rand(30000, 100000));
            $SQL = "SELECT C.category_id
                    FROM " . DB_PREFIX . "category C
                    INNER JOIN " . DB_PREFIX . "category_description CD ON CD.category_id=C.category_id
                    WHERE CD.name = '{$category_array['Name']}' AND C.`api_id` = '{$category_array['ID']}'";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows) {
                if ($row = check_existence($this->db, DB_PREFIX . 'category', 'api_id', $category_array['ID'], 'category_id')) {
                    if ($row = check_existence($this->db, DB_PREFIX . 'category_description', 'name', $category_array['Name'], 'category_id')) {
                        usleep(rand(30000, 100000));
                        $SQL = "UPDATE `" . DB_PREFIX . "category`
                                SET `api_id` = '" . $this->db->escape($category_array['ID']) . "'
                                WHERE `category_id` = '" . (int) $row['category_id'] . "';";
                        db_query_handler($this->db, $SQL, true);
                    }
                }

                $top = isset($category_array['ParentID']) ? 0 : 1;
                usleep(rand(30000, 100000));
                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category`
                        SET `parent_id` = 0,
                            `top` = $top,
                            `column` = 1,
                            `sort_order` = 0,
                            `api_id` = '" . $this->db->escape($category_array['ID']) . "',
                            `api_custom_field` = '" . $this->db->escape($category_array['ParentID']) . "',
                            `status` = 1,
                            `date_added` = NOW(),
                            `date_modified` = NOW();";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int) $this->db->getLastId();

                if ($last_inserted_id) {
                    //Set category parent for public array '$this->path_array'. If it has no parent it gets category id.
                    if (!isset($category_array['ParentID'])) {
                        $this->path_array[$category_array['ID']] = $last_inserted_id;
                    } else {
                        $parent = isset($this->path_array[$category_array['ParentID'] . ""]) ? $this->path_array[$category_array['ParentID'] . ""] : $category_array['ParentID'] . "";
                        $this->path_array[$category_array['ID']] = $parent . ',' . $last_inserted_id;
                    }

                    //There may be many stores. In that case we should use other logic (foreach).
                    usleep(rand(30000, 100000));
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category_to_store`
                        SET `category_id` = " . $last_inserted_id . ",
                            `store_id` = 0;";
                    db_query_handler($this->db, $SQL);

                    usleep(rand(30000, 100000));
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category_to_layout`
                            SET `category_id` = " . $last_inserted_id . ",
                                `store_id` = 0,
                                `layout_id` = 0;";
                    db_query_handler($this->db, $SQL);
                } else {
                    usleep(rand(30000, 100000));
                    $SQL = "SELECT `C`.`category_id`
                            FROM " . DB_PREFIX . "category C
                            WHERE `C`.`api_id` = '{$category_array['ID']}'";
                    $result = db_query_handler($this->db, $SQL, true);
                    $last_inserted_id = $result->row['category_id'];
                }

                foreach ($this->language_ids as $key => $id) {
                    $foreign_name = isset($category_array['categoryForeignName']) ? $category_array['categoryForeignName'] : $category_array['Name'];
                    $language_name = $key == 0 ? $foreign_name : $category_array['Name'];
                    usleep(rand(30000, 100000));
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category_description`
                            SET `category_id` = $last_inserted_id,
                                `language_id` = " . (int) $id . ",
                                `name` = '" . $this->db->escape($language_name) . "',
                                `description` = '',
                                `meta_title` = '" . $this->db->escape($language_name) . "',
                                `meta_description` = '',
                                `meta_keyword` = ''
                            ON DUPLICATE KEY UPDATE
                                `name` = VALUES(`name`);";
                    db_query_handler($this->db, $SQL, true);
                }
            }
        }

        //Remove parent id from column `api_custom_field`.
        usleep(rand(30000, 100000));
        $SQL = "SELECT `category_id`, `api_custom_field`
                FROM `" . DB_PREFIX . "category`
                WHERE `api_custom_field` <> '';";
        $result = db_query_handler($this->db, $SQL, true);
        foreach ($result->rows as $row) {
            usleep(rand(30000, 100000));
            $SQL = "SELECT `category_id`
                    FROM `" . DB_PREFIX . "category`
                    WHERE `api_id` = '" . $this->db->escape($row['api_custom_field']) . "';";
            $result2 = db_query_handler($this->db, $SQL, true);
            if ($result2->num_rows) {
                $parent_category_id = (int) $result2->row['category_id'];
                usleep(rand(30000, 100000));
                $SQL = "UPDATE `" . DB_PREFIX . "category`
                        SET `parent_id` = {$parent_category_id},
                            `api_custom_field` = ''
                        WHERE `category_id` = '" . $this->db->escape($row['category_id']) . "';";
                db_query_handler($this->db, $SQL, true);
            }
        }

        /*
        Insert values into table `category_path`.
        Use find path function to find all parents of a category.
         */
        $this->findPath();
        foreach ($this->path_array as $key => $value) {
            $exploded_value = explode(',', $value);
            $level = 0;
            foreach ($exploded_value as $val) {
                $parent_query = null;
                if (is_numeric($val)) {
                    $parent_query = $val;
                } else {
                    if (is_string($val)) {
                        $parent_query = "(
                            SELECT `category_id`
                            FROM `" . DB_PREFIX . "category`
                            WHERE `api_id` = '" . $this->db->escape($val) . "'
                        )";
                    }
                }

                if (isset($parent_query)) {
                    usleep(rand(30000, 100000));
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category_path` (`category_id`, `path_id`, `level`) VALUES
                            (
                                (
                                    SELECT `category_id`
                                    FROM `" . DB_PREFIX . "category`
                                    WHERE `api_id` = '" . $this->db->escape($key) . "'
                                ),
                                $parent_query,$level
                            );";
                    db_query_handler($this->db, $SQL, true);
                    $level++;
                }
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit) {
            log_error('[Failed Transaction Commit]', 'integrateCategory');
            $this->db->close();
            exit();
        }
    }

    //Find category tree.
    private function findPath(): void
    {
        foreach ($this->path_array as $key => $value) {
            $exploded_value = explode(',', $value);
            if (isset($exploded_value)) {
                $this->findParent($key, $exploded_value[0]);
            }
        }
    }

    private function findParent($key, $val): void
    {
        $parent = is_numeric($val) ? '`category_id`' : '`api_id`';

        usleep(rand(30000, 100000));
        $SQL = "SELECT `parent_id`
                FROM `" . DB_PREFIX . "category`
                WHERE $parent = '$val';";
        $result = db_query_handler($this->db, $SQL, true);
        if ($result->num_rows) {
            if ($result->row['parent_id'] != 0) {
                $this->path_array[$key] = $result->row['parent_id'] . ',' . $this->path_array[$key];
                $this->findParent($key, $result->row['parent_id']);
            }
        }
    }
}
