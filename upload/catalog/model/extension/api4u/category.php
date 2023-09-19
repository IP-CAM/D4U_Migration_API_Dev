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
        "categoryParentId" => "Category parent id or null",
        "categoryName" => "Category name"
       );
     */
    public function integrateCategory($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on category.');
            return;
        }

        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateCategory.');
            return;
        }

        //Iterate through categories
        foreach ($data as $category_array)
        {
            if (!isset($category_array['categoryName']) || !isset($category_array['categoryId']))
            {
                continue;
            }

            /*
             * Tables `category`, `category_description`, `category_to_store`, `category_to_store`, `category_to_layout`
             * If category already exists, skip.
             * If category is new, insert.
             * If category description changed, update.
             */
            $SQL = "SELECT C.category_id
                    FROM " . DB_PREFIX . "category C 
                    INNER JOIN " . DB_PREFIX . "category_description CD ON CD.category_id=C.category_id
                    WHERE CD.name = '{$category_array['categoryName']}' AND C.`api_id` = '{$category_array['categoryId']}'";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {
                if ($row = check_existence($this->db, DB_PREFIX . 'category_description', 'name', $category_array['categoryName'], 'category_id'))
                {
                    $SQL = "UPDATE `" . DB_PREFIX . "category`
                            SET `api_id` = '" . $this->db->escape($category_array['categoryId']) . "'
                            WHERE `category_id` = '" . (int)$row['category_id'] . "';";
                    db_query_handler($this->db, $SQL, true);
                    continue;
                }

                $category_parent_id =
                $top = isset($category_array['categoryParentId']) ? 0 : 1;
                $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category` 
                        SET `parent_id` = 0,
                            `top` = $top, 
                            `column` = 1,
                            `sort_order` = 0, 
                            `api_id` = '" . $this->db->escape($category_array['categoryId']) . "', 
                            `api_custom_field` = '" . $this->db->escape($category_array['categoryParentId']) . "', 
                            `status` = 1,
                            `date_added` = NOW(),
                            `date_modified` = NOW();";
                db_query_handler($this->db, $SQL, true);
                $last_inserted_id = (int)$this->db->getLastId();

                if ($last_inserted_id)
                {
                    //Set category parent for public array '$this->path_array'. If it has no parent it gets category id.
                    if (!isset($category_array['categoryParentId']))
                    {
                        $this->path_array[$category_array['categoryId']] = $last_inserted_id;
                    }
                    else
                    {
                        $parent = isset($this->path_array[$category_array['categoryParentId'] . ""]) ? $this->path_array[$category_array['categoryParentId'] . ""] : $category_array['categoryParentId'] . "";
                        $this->path_array[$category_array['categoryId']] = $parent . ',' . $last_inserted_id;
                    }

                    //There may be many stores. In that case we should use other logic (foreach).
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category_to_store`
                        SET `category_id` = " . $last_inserted_id . ",
                            `store_id` = 0;";
                    db_query_handler($this->db, $SQL);

                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category_to_layout`
                            SET `category_id` = " . $last_inserted_id . ",
                                `store_id` = 0,
                                `layout_id` = 0;";
                    db_query_handler($this->db, $SQL);
                }
                else
                {
                    $SQL = "SELECT `C`.`category_id`
                            FROM " . DB_PREFIX . "category C
                            WHERE `C`.`api_id` = '{$category_array['categoryId']}'";
                    $result = db_query_handler($this->db, $SQL, true);
                    $last_inserted_id = $result->row['category_id'];
                }

                foreach ($this->language_ids as $key => $id)
                {
                    $foreign_name = isset($category_array['categoryForeignName']) ? $category_array['categoryForeignName'] : $category_array['categoryName'];
                    $language_name = $key == 0 ? $foreign_name : $category_array['categoryName'];
                    $SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "category_description`
                            SET `category_id` = $last_inserted_id,
                                `language_id` = " . (int)$id . ",
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
        $SQL = "SELECT `category_id`, `api_custom_field`
                FROM `" . DB_PREFIX . "category`
                WHERE `api_custom_field` <> '';";
        $result = db_query_handler($this->db, $SQL, true);
        foreach ($result->rows as $row)
        {
            $SQL = "SELECT `category_id`
                    FROM `" . DB_PREFIX . "category`
                    WHERE `api_id` = '" . $this->db->escape($row['api_custom_field']) . "';";
            $result2 = db_query_handler($this->db, $SQL, true);
            if ($result2->num_rows)
            {
                $parent_category_id = (int)$result2->row['category_id'];
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
        foreach ($this->path_array as $key => $value)
        {
            $exploded_value = explode(',', $value);
            $level = 0;
            foreach ($exploded_value as $val)
            {
                $parent_query = null;
                if (is_numeric($val))
                {
                    $parent_query = $val;
                }
                else
                {
                    if (is_string($val))
                    {
                        $parent_query = "(
                            SELECT `category_id`
                            FROM `" . DB_PREFIX . "category`
                            WHERE `api_id` = '" . $this->db->escape($val) . "'
                        )";
                    }
                }

                if (isset($parent_query))
                {
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
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction Commit]', 'integrateCategory');
            exit();
        }
    }

    //Find category tree.
    private function findPath(): void
    {
        foreach ($this->path_array as $key => $value)
        {
            $exploded_value = explode(',', $value);
            if (isset($exploded_value))
            {
                $this->findParent($key, $exploded_value[0]);
            }
        }
    }

    private function findParent($key, $val): void
    {
        $parent = is_numeric($val) ? '`category_id`' : '`api_id`';
        $SQL = "SELECT `parent_id`
                FROM `" . DB_PREFIX . "category`
                WHERE $parent = '$val';";
        $result = db_query_handler($this->db, $SQL, true);
        if ($result->num_rows)
        {
            if ($result->row['parent_id'] != 0)
            {
                $this->path_array[$key] = $result->row['parent_id'] . ',' . $this->path_array[$key];
                $this->findParent($key, $result->row['parent_id']);
            }
        }
    }
}