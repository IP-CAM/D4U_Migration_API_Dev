<?php

class ModelExtensionModuleApi4uMigrations extends Model
{
    public function up()
    {
        $this->createLogRequestUp();
        $this->updateCategoryUp();
        $this->updateAttributeGroupTableUp();
        $this->updateAttributeTableUp();
        $this->updateFilterGroupTableUp();
        $this->updateFilterTableUp();
        $this->updateOptionTableUp();
        $this->updateOptionValueTableUp();
        $this->updateProductTableUp();
        $this->updateProductImageTableUp();
        $this->updatePoipOptionImageTableUp();
        $this->updateManufacturerTableUp();
    }

    public function down()
    {
        $this->createLogRequestDown();
        $this->updateCategoryDown();
        $this->updateAttributeGroupTableDown();
        $this->updateAttributeTableDown();
        $this->updateFilterGroupTableDown();
        $this->updateFilterTableDown();
        $this->updateOptionTableUpDown();
        $this->updateOptionValueTableDown();
        $this->updateProductTableDown();
        $this->updateProductImageTableDown();
        $this->updatePoipOptionImageTableDown();
        $this->updateManufacturerTableDown();
    }

    //Up
    public function createLogRequestUp()
    {
      usleep(rand(100000, 200000));
        $SQL = "CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "log_request` (
                    `id` INT(11) NOT NULL AUTO_INCREMENT,
                    `url` VARCHAR(255) NOT NULL COLLATE 'utf8mb4_unicode_520_ci',
                    `parameters` TEXT NOT NULL COLLATE 'utf8mb4_unicode_520_ci',
                    `code` VARCHAR(5) NOT NULL COLLATE 'utf8mb4_unicode_520_ci',
                    `raw_response_header` VARCHAR(255) COLLATE 'utf8mb4_unicode_520_ci',
                    `raw_response` TEXT COLLATE 'utf8mb4_unicode_520_ci',
                    `datetime` DATETIME NOT NULL,
                    `error` VARCHAR(255) COLLATE 'utf8mb4_unicode_520_ci',
                    `attempts` TINYINT(1) NOT NULL,
                    `retries` TINYINT(1) NOT NULL DEFAULT '0',
                    PRIMARY KEY (`id`) USING BTREE
                )
                COLLATE='utf8mb4_unicode_520_ci'
                ENGINE=InnoDB
                AUTO_INCREMENT=3;";
        db_query_handler($this->db, $SQL, 0, 0);
    }

    public function checkIfColumnExists($datas) 
    {   
        if(!empty($datas[0])) {
            // Check if the Column Exists
            $checkSQL = "select * from information_schema.COLUMNS 
                        where `table_schema`='".DB_DATABASE."' 
                        and `table_name`='".DB_PREFIX.$datas[0]."' 
                        and `column_name`='".$datas[1]."';";
            $result = db_query_handler($this->db, $checkSQL, 0, 0);

            // If the column doesn't exist, add it
            if (!empty($result->num_rows) && $result->num_rows > 0) { return 1; }
            else { return 0; }
        }
    }

    public function runQueries($tablesData) {
        if(!empty($tablesData[1])) { // Multiple Arrays
            foreach($tablesData as $datas) {
                $columnExists = $this->checkIfColumnExists($datas);
                //echo 'Trying<br/>';
    
                if($columnExists == 0 || ($columnExists == 1 && stripos($datas[2], 'DROP'))) { // If the column Doesn't exists or it's a Drop query, only then, run it
                    $SQL = "ALTER TABLE `" . DB_PREFIX . $datas[0] ."`";
                    $SQL .= (string) $datas[2];
                    $SQL .= ";";
                    
                    db_query_handler($this->db, $SQL, 0, 0);
                    echo 'Done<br/>';
                    
                }
                
                //echo 'End<br/>';
            }
        } else { // Single Array
            $datas = $tablesData[0];
            $columnExists = $this->checkIfColumnExists($datas);
            //echo 'Trying<br/>';

            if($columnExists == 0 || ($columnExists == 1 && stripos($datas[2], 'DROP'))) { // If the column Doesn't exists or it's a Drop query, only then, run it
                $SQL = "ALTER TABLE `" . DB_PREFIX . $datas[0] ."`";
                $SQL .= $datas[2];
                $SQL .= ";";
                
                db_query_handler($this->db, $SQL, 0, 0);
                echo 'Done<br/>';
                
            }
            //echo 'End<br/>';
        }
    }

    public function updateCategoryUp()
    {
        $tablesData = array(
            // Update Category Up
            array('category', 'api_id', "ADD `api_id` VARCHAR(255) AFTER `sort_order`"),
            array('category', 'api_custom_field', "ADD `api_custom_field` VARCHAR(255) NOT NULL AFTER `api_id`"),
            array('category', 'category_api_id_index', "ADD CONSTRAINT `category_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    

    public function updateAttributeGroupTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('attribute_group', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('attribute_group', 'attribute_group_api_id_index', "ADD CONSTRAINT `attribute_group_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateAttributeTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('attribute', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('attribute', 'attribute_api_id_index', "ADD CONSTRAINT `attribute_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateFilterGroupTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('filter_group', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('filter_group', 'filter_group_api_id_index', "ADD CONSTRAINT `filter_group_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateFilterTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('filter', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('filter', 'filter_api_id_index', "ADD CONSTRAINT `filter_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateOptionTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('option', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('option', 'option_api_id_index', "ADD CONSTRAINT `option_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateOptionValueTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('option_value', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('option_value', 'option_value_api_id_index', "ADD CONSTRAINT `option_value_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateProductTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('product', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `stock_status_id`"),
            array('product', 'api_custom_field', "ADD `api_custom_field` VARCHAR(255) NOT NULL AFTER `api_id`"),
            array('product', 'product_api_id_index', "ADD CONSTRAINT `product_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateProductImageTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('product_image', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('product_image', 'product_image_api_id_index', "ADD CONSTRAINT `product_image_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updatePoipOptionImageTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('poip_option_image', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('poip_option_image', 'poip_image_api_id_index', "ADD CONSTRAINT `poip_image_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    public function updateManufacturerTableUp()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('manufacturer', 'api_id', "ADD `api_id` VARCHAR(128) AFTER `sort_order`"),
            array('manufacturer', 'manufacturer_api_id_index', "ADD CONSTRAINT `manufacturer_api_id_index` UNIQUE (`api_id`)")
        );

        $this->runQueries($tablesData);
    }

    //Down
    public function createLogRequestDown()
    {
        usleep(rand(100000, 200000));
        $SQL = "DROP TABLE IF EXISTS`" . DB_PREFIX . "log_request`;";
        db_query_handler($this->db, $SQL, 0, 0);
    }

    public function updateCategoryDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('category', 'api_id', "DROP COLUMN `api_id`"),
            array('category', 'api_custom_field', "DROP COLUMN `api_custom_field`")
        );

        $this->runQueries($tablesData);
    }

    public function updateAttributeGroupTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('attribute_group', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updateAttributeTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('attribute', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updateFilterGroupTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('filter_group', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updateFilterTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('filter', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updateOptionTableUpDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('option', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updateOptionValueTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('option_value', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updateProductTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('product', 'api_id', "DROP COLUMN `api_id`"),
            array('product', 'api_custom_field', "DROP COLUMN `api_custom_field`")
        );

        $this->runQueries($tablesData);
    }

    public function updateProductImageTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('product_image', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updatePoipOptionImageTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('poip_option_image', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }

    public function updateManufacturerTableDown()
    {
        $tablesData = array(
            // Update Attribute Group Table Up
            array('manufacturer', 'api_id', "DROP COLUMN `api_id`")
        );

        $this->runQueries($tablesData);
    }
}