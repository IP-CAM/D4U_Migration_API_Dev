<?php

class ModelExtensionApi4uManufacturer extends Model
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
        "manufacturerId" => "ERP manufacturer id",
        "manufacturerName" => "Manufacturer group name"
       );
     */
    public function integrateManufacturer($data = array()): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on manufacturer.');
            return;
        }

        //Begin transaction
        $transaction = $this->db->beginTransaction();
        if (!$transaction)
        {
            log_error('[Failed Transaction]', 'integrateManufacturer');
            $this->db->close();
            exit();
        }

        //Iterate through group manufacturers
        foreach ($data as $manufacturer_array)
        {
            if (!isset($manufacturer_array['manufacturerName']) || !isset($manufacturer_array['manufacturerId']))
            {
                continue;
            }

            /*
             * Tables `manufacturer`
             * If manufacturer already exists, skip.
             * If manufacturer is new, insert.
             * If manufacturer name changed, update.
             */
            usleep(rand(30000, 100000));
$SQL = "SELECT `M`.`manufacturer_id`
                    FROM `" . DB_PREFIX . "manufacturer` M
                    WHERE `M`.`name` = '" . $this->db->escape($manufacturer_array['manufacturerName']) . "' AND `M`.`api_id` = '" . $this->db->escape($manufacturer_array['manufacturerId']) . "';";
            $result = db_query_handler($this->db, $SQL, true);
            if (!$result->num_rows)
            {

                usleep(rand(30000, 100000));
$SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "manufacturer` 
                        SET `name` = '" . $this->db->escape($manufacturer_array['manufacturerName']) . "',
                            `api_id` = '" . $this->db->escape($manufacturer_array['manufacturerId']) . "',
                            `sort_order` = 0;";
                db_query_handler($this->db, $SQL, true);
            }
        }

        $transaction_commit = $this->db->commitTransaction();
        if (!$transaction_commit)
        {
            log_error('[Failed Transaction Commit]', 'integrateManufacturer');
            $this->db->close();
            exit();
        }
    }
}