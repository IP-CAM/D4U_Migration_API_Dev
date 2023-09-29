<?php
error_reporting(E_ALL);
ini_set('error_reporting', E_ALL);

//OPENCART
define('API4U VERSION', '3.0.3.8');

//ERP Parameters
define('ERP_API_URL', '');
define('API_CODE_0', '');
// define('API_CODE_1', '');
define('API_APP_NAME', '');
define('API_DB_ALIAS', '');
define('API_DB_USERNAME', '');
define('API_DB_PASSWORD', '');
define('API_PACKAGE_SIZE', '1000');

//General Options 
define('NEEDS_LOGIN_TOKEN', true);
define('IS_POST_OR_GET', true);
define('INTEGRATE_PROCESS', ['']);

//Product Inputs
define('PRODUCTS_JSON_DATA_MAP_POINT', ['']);
define('PRODUCTS_JSON_DATA_ENDPOINT', '');

//Categories Inputs
define('CATEGORIES_JSON_DATA_MAP_POINT', ['Data', 'ItemTreeCategories']);
define('CATEGORIES_JSON_DATA_ENDPOINT', 'ItemCategories');


//ERP Endpoints
define('ELOG_URL', ERP_API_URL.'/exesjson/elogin');
define('CHECK_TOKEN_URL', ERP_API_URL.'/exesjson/checkcookie');
define('POST_DATA_URL', ERP_API_URL.'/exesjson/postdata');
define('GET_DATA_URL', ERP_API_URL.'/exesjson/getdata');

//Token txt for token_check
define('API4U_LIBRARY', DIR_SYSTEM . 'library/api4u/');
define('TOKEN_FILE', API4U_LIBRARY . 'token_store');

// Images
define('IMAGES', DIR_IMAGE . 'catalog/product-upload/');

//API4U
define('API4U_COMMON', DIR_SYSTEM . 'library/api4u/common_functions.php');
define('API4U_INTEGRATION', DIR_APPLICATION . 'controller/extension/api4u/integration.php');