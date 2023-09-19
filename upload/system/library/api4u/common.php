<?php

date_default_timezone_set('Europe/Athens');

if (PHP_SAPI != 'cli')
{
    header("HTTP/1.1 403 Forbidden");
    exit();
}

define('VERSION', '3.0.3.8');
require(realpath(dirname(__DIR__) . '/../..') . '/config.php');
require(DIR_SYSTEM . 'startup.php');
require(DIR_SYSTEM . 'library/api4u/common_functions.php');
require(DIR_SYSTEM . 'library/api4u/config.php');

$db = db_connection();

function garbage_collector()
{
    global $db;
    log_request_garbage_collector();
    delete_excel_garbage_collector();
}

function log_request_garbage_collector()
{
    global $db;
    $ids = array();

    $SQL = "SELECT id
            FROM " . DB_PREFIX . "log_request
            ORDER BY id DESC LIMIT 1000;";
    $result = db_query_handler($db, $SQL);
    if (!$result)
    {
        exit();
    }

    if ($result->num_rows > 0)
    {
        foreach ($result->rows as $value)
        {
            $ids[] = "'" . $value['id'];
        }
    }

    if (!empty($ids))
    {
        $SQL = "DELETE
                FROM " . DB_PREFIX . "log_request
                WHERE id NOT IN(" . implode("',", $ids) . "'" . ");";
        $result = db_query_handler($db, $SQL);
    }
}

function delete_excel_garbage_collector()
{
    $excel_files = glob(IMAGES . '*.xlsx');
    foreach ($excel_files as $file)
    {
        $modification_timestamp = filectime("$file");
        if ($modification_timestamp)
        {
            $day_difference = date_diff(date_create(date('d-m-Y', $modification_timestamp)), date_create());
            if ($day_difference->days >= 3)
            {
                if (is_file($file))
                {
                    unlink($file);
                }
            }
        }
    }
}