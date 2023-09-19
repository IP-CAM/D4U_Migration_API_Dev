<?php

//OPENCART
define('API4U VERSION', '3.0.3.8');

//ERP Parameters
define('API_CODE_0', '');
// define('API_CODE_1', '');
define('API_APP_NAME', '');
define('API_DB_ALIAS', '');
define('API_DB_USERNAME', '');
define('API_DB_PASSWORD', '');
define('API_PACKAGE_SIZE', '');

//Configs
define('NEEDS_LOGIN_TOKEN', true);
define('IS_POST_OR_GET', false);
define('API_URL', '');
define('JSON_DECODE_PRODUCT', true);

//Field Name of Product Data in JSON/Array
define('PRODUCT_FIELD_NAME_IN_JSON', '');

//ERP Endpoints
define('ELOG_URL', API_URL.'/exesjson/elogin');
define('CHECK_TOKEN_URL', API_URL.'/exesjson/checkcookie');
define('POST_DATA_URL', API_URL.'/exesjson/postdata');
define('GET_DATA_URL', API_URL.'/exesjson/getdata');

//Token txt for token_check
define('API4U_LIBRARY', DIR_SYSTEM . 'library/api4u/');
define('TOKEN_FILE', API4U_LIBRARY . 'token_store');

// Images
define('IMAGES', DIR_IMAGE . 'catalog/product-upload/');

//API4U
define('API4U_COMMON', DIR_SYSTEM . 'library/api4u/common_functions.php');
define('API4U_INTEGRATION', DIR_APPLICATION . 'controller/extension/api4u/integration.php');