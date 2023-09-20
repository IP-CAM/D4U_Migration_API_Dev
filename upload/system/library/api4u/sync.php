<?php

ini_set('memory_limit', '1024M');

if (PHP_SAPI != 'cli')
{
    header("HTTP/1.1 403 Forbidden");
    exit();
}

require(realpath(dirname(__DIR__) . '/../..') . '/config.php');
require_once(DIR_SYSTEM . 'library/api4u/config.php');
require(DIR_SYSTEM . 'startup.php');
require(API4U_COMMON);

$error_file = 'error.log';

// Registry
$registry = new Registry();

// Config
$config = new Config();
$config->load('default');

// Loader
$loader = new Loader($registry);
$registry->set('load', $loader);

// Event
$event = new Event($registry);
$registry->set('event', $event);

// Database
$db = db_connection();
$registry->set('db', $db);

//Error file
usleep(rand(30000, 100000));
$SQL = "SELECT `value`
        FROM `" . DB_PREFIX . "setting`
        WHERE `key` = 'config_error_filename';";
$result = db_query_handler($db, $SQL);
if ($result->num_rows)
{
    $config->set('error_filename', $result->row['value']);
    $error_file = $result->row['value'];
}

define('API4U_ERROR_FILE', $error_file);

// Language
usleep(rand(30000, 100000));
$SQL = "SELECT `language_id`
        FROM `" . DB_PREFIX . "language`;";
$result = db_query_handler($db, $SQL);
if ($result->num_rows)
{
    $config->set('config_language_ids', array_map('set_languages', $result->rows));
}

// Customer group
usleep(rand(30000, 100000));
$SQL = "SELECT `customer_group_id`
        FROM `" . DB_PREFIX . "customer_group`;";
$result = db_query_handler($db, $SQL);
if ($result->num_rows)
{
    $config->set('config_customer_group_ids', array_map('set_customer_groups', $result->rows));
}

//set config after getting new values
$registry->set('config', $config);

########## BLOCK START ##########
#NOTIFY SHIPPED ORDERS
/* Session
 * We set a custom session in order to notify (module) clients about their shipped orders
 */
//$session = new Session($config->get('session_engine'), $registry);
//$registry->set('session', $session);
//$session->start();
//$session_id = $session->getId();
//$session->data['api_id'] = 1;
//$session->start($session_id);
//
//usleep(rand(30000, 100000));
$SQL = "INSERT INTO `" . DB_PREFIX . "session`(`session_id`, `data`, `expire`) VALUES
//            ('$session_id', '{\"api_id\":\"1\"}', DATE_ADD(NOW(), INTERVAL 2 MINUTE));";
//db_query_handler($db, $SQL);
//
//usleep(rand(30000, 100000));
$SQL = "INSERT INTO `" . DB_PREFIX . "api_session`(`api_id`, `session_id`, `ip`, `date_added`, `date_modified`)
//        VALUES (1, '$session_id', '" . gethostbyname("www.navygreen-multi.demod4u.gr") . "', NOW(), NOW());";
//db_query_handler($db, $SQL);
//
//usleep(rand(30000, 100000));
$SQL = "SELECT `ip`
//        FROM `" . DB_PREFIX . "api_ip`
//        WHERE `ip` = '" . gethostbyname("www.navygreen-multi.demod4u.gr") . "' AND `api_id` = 1;";
//$result = db_query_handler($db, $SQL);
//
//if (!$result->num_rows)
//{
//    usleep(rand(30000, 100000));
$SQL = "INSERT INTO `" . DB_PREFIX . "api_ip`(`api_id`, `ip`)
//            VALUES (1, '" . gethostbyname("www.navygreen-multi.demod4u.gr") . "');";
//    db_query_handler($db, $SQL);
//}
########## BLOCK END ############

require_once(API4U_INTEGRATION);
require_once(API4U_LIBRARY . 'Curl/curl_include.php');
require_once(API4U_LIBRARY . 'APIExecution.php');
require_once(API4U_LIBRARY . 'PHP_XLSXWriter/xlsxwriter.class.php');

$api4u = new ControllerApi4uIntegration($registry);
$api4u->index();
