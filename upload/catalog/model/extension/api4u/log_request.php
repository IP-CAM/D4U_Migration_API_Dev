<?php

class ModelExtensionApi4uLogRequest extends Model
{
    public function log($data = array()): void
    {
        if (!empty($data))
        {
            usleep(rand(30000, 100000));
$SQL = "INSERT IGNORE INTO `" . DB_PREFIX . "log_request` SET 
                    `url` = '" . $this->db->escape($data['url']) . "',
                    `parameters` = '" . $this->db->escape($data['parameters']) . "',
                    `code`= '" . $this->db->escape($data['code']) . "',
                    `raw_response_header`= '" . $this->db->escape($data['raw_response_header']) . "',
                    `raw_response`= '" . $this->db->escape($data['raw_response']) . "',
                    `datetime` = NOW(),
                    `error`= '" . $this->db->escape($data['error']) . "',
                    `attempts`= '" . (int)$data['attempts'] . "',
                    `retries`= '" . (int)$data['retries'] . "'";
            db_query_handler($this->db, $SQL);
        }
    }
}