<?php

function log_error($header, $error_message): void
{
    date_default_timezone_set('Europe/Athens');
    $time = date('d/m/Y H:i:s', time());
    error_log("[$time] $header $error_message \n", 3, DIR_SYSTEM . "storage/logs/" . API4U_ERROR_FILE);
}

function log_image_error($image): void
{
    date_default_timezone_set('Europe/Athens');
    $time = date('d/m/Y H:i:s', time());
    error_log("[$time] Missing image: '$image' \n", 3, IMAGES . 'missing_images.log');
}

function db_connection(): ?object
{
    $db = null;

    try
    {
        $db = new DB(DB_DRIVER, DB_HOSTNAME, DB_USERNAME, DB_PASSWORD, DB_DATABASE);
    } catch (Exception $e)
    {
        log_error("[DB ERROR]", 'Failed to connect on DB');
        exit();
    }

    return $db;
}

function db_query_handler($db, $SQL, $rollback = false, $exit_on_error = true)
{
    $result = false;

    try
    {
        $result = $db->query($SQL);
    } catch (Exception $e)
    {
        if ($rollback)
        {
            $db->rollbackTransaction();
        }
        log_error('[Failed query] Fatal error:', $e->getMessage());
        echo 'Cron process finished errors.';
        if ($exit_on_error)
        {
            exit();
        }
    }

    return $result;
}

function set_languages($value)
{
    return $value['language_id'];
}

function set_customer_groups($value)
{
    return $value['customer_group_id'];
}

function insertUpdateValues($db, $SQL, $SQL_INSERT_VALUES, $table, $on_duplicate_value = '')
{
    if ($SQL_INSERT_VALUES == '')
    {
        log_error("[API4U] Warning:", "Table `" . DB_PREFIX . "$table` has not been updated.");
    }
    else
    {
        $on_duplicate_value = $on_duplicate_value != '' ? 'ON DUPLICATE KEY UPDATE ' . $on_duplicate_value : '';
        usleep(rand(100000, 200000));
        $SQL_INSERT_VALUES = rtrim($SQL_INSERT_VALUES, ',');        

        usleep(rand(100000, 200000));
        $SQL .= $SQL_INSERT_VALUES . $on_duplicate_value;
        db_query_handler($db, $SQL, true);
    }
}

function main_image_selection($db, $model, $excluded_image): string
{
    $SQL = "SELECT `image`
            FROM `" . DB_PREFIX . "poip_option_image`
            WHERE `product_id` = (SELECT `product_id`
                                  FROM `" . DB_PREFIX . "product`
                                  WHERE `model` = '$model'
                                  LIMIT 1) AND `image` NOT LIKE '%$excluded_image%';";
    $result = db_query_handler($db, $SQL, true);
    foreach ($result->rows as $row)
    {
        if (!isset($row['image']))
        {
            continue;
        }

        if (file_exists(DIR_IMAGE . $row['image']))
        {
            return $row['image'];
        }
    }
    return 'no_image.png';
}

function check_existence($db, $table, $column, $value, $column_value): array
{
    $SQL = "SELECT `$column_value`
            FROM `$table`
            WHERE `$column` = '$value';";
    $result = db_query_handler($db, $SQL, true);
    return $result->row;
}