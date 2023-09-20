<?php

class ModelExtensionApi4uExportExcel extends Model
{
    public function index($store = 0): object
    {        

          usleep(rand(30000, 100000));
$SQL = "SELECT `P`.`model`, `PD`.`name`, `P`.`price`, `PS`.`price` AS special_price, `P`.`date_added`, `OVD`.`name` AS filter_name, IF(`P`.`status`, 'ΕΝΕΡΓΟ', 'ΑΝΕΝΕΡΓΟ') AS status, `P`.`image`, 
                        substring_index(GROUP_CONCAT(IF(`PI`.`image` LIKE CONCAT('%',  REPLACE(REPLACE(`OVD`.`name`, \"/\", \"$\"), \" \", \"$\") ,'%'), `PI`.`image`, NULL)), ',', 5) AS images,
                       `POV`.`quantity`, `FD`.`name` AS season
                FROM `" . DB_PREFIX . "product` P
                LEFT JOIN `" . DB_PREFIX . "product_description` PD ON `PD`.product_id = `P`.`product_id`
                LEFT JOIN `" . DB_PREFIX . "product_special` PS ON `PS`.`product_id` = `P`.`product_id`
                LEFT JOIN `" . DB_PREFIX . "product_option_value` POV ON `POV`.`product_id` = `P`.`product_id`
                INNER JOIN `" . DB_PREFIX . "option_value_description` OVD ON `OVD`.`option_value_id` = `POV`.`option_value_id`
                    AND `OVD`.option_id = 1
                LEFT JOIN `" . DB_PREFIX . "product_image` PI ON `PI`.product_id = `P`.`product_id`
                
                LEFT JOIN `" . DB_PREFIX . "product_filter` PF ON `PF`.product_id = `P`.`product_id`
                LEFT JOIN `" . DB_PREFIX . "filter_description` FD ON `FD`.`filter_id` = `PF`.`filter_id`
                    AND `FD`.`filter_group_id` = (SELECT `filter_group_id`
                                                  FROM `" . DB_PREFIX . "filter_group_description` FGD
                                                  WHERE `FGD`. `name` = 'Season'
                                                  LIMIT 1)
                WHERE `P`.`api_custom_field` = '$store' AND `PD`.`language_id` = 2 AND `FD`.`name` IS NOT NULL
                GROUP BY  `P`.`model`, `PD`.name, `P`.`price`, `PS`.`price`, `P`.`date_added`, `OVD`.`name`, `P`.`image`, `P`.`quantity`, `POV`.`quantity`,`P`.`product_id`, `FD`.`name`;";
        return db_query_handler($this->db, $SQL);
    }
}