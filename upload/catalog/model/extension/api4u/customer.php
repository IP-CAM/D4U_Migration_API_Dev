<?php

class ModelExtensionApi4uCustomer extends Model
{
    public function updateCustomer($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on update customer.');
            return;
        }
        usleep(rand(100000, 200000));
        $SQL = "UPDATE `" . DB_PREFIX . "customer`
                SET `api_id` = '" . $this->db->escape($data['customer_erp_id']) . "'
                WHERE customer_id = " . (int)$data['customer_id'] . ";";
        db_query_handler($this->db, $SQL);
    }

    public function updateGuestCustomer($data): void
    {
        if (empty($data))
        {
            log_error("[API4U] Warning:", 'Empty data array on update guest customer.');
            return;
        }
        usleep(rand(100000, 200000));
        $SQL = "SELECT `api_id`
                FROM `" . DB_PREFIX . "order`
                WHERE `api_id` = '" . $this->db->escape($data['customer_erp_id']) . "';";
        $result = db_query_handler($this->db, $SQL);
        if (!$result->num_rows)
        {
            $SQL = "UPDATE `" . DB_PREFIX . "order`
                    SET `api_id` = '" . $this->db->escape($data['customer_erp_id']) . "'
                    WHERE `order_id` = " . (int)$data['order_id'] . ";";
            db_query_handler($this->db, $SQL);
        }
    }
}