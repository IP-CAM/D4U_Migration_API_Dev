```

            ██████╗ ██╗ ██████╗ ██╗████████╗ █████╗ ██╗         ██╗  ██╗    ██╗   ██╗ ██████╗ ██╗   ██╗
            ██╔══██╗██║██╔════╝ ██║╚══██╔══╝██╔══██╗██║         ██║  ██║    ╚██╗ ██╔╝██╔═══██╗██║   ██║
            ██║  ██║██║██║  ███╗██║   ██║   ███████║██║         ███████║     ╚████╔╝ ██║   ██║██║   ██║
            ██║  ██║██║██║   ██║██║   ██║   ██╔══██║██║         ╚════██║      ╚██╔╝  ██║   ██║██║   ██║
            ██████╔╝██║╚██████╔╝██║   ██║   ██║  ██║███████╗         ██║       ██║   ╚██████╔╝╚██████╔╝
            ╚═════╝ ╚═╝ ╚═════╝ ╚═╝   ╚═╝   ╚═╝  ╚═╝╚══════╝         ╚═╝       ╚═╝    ╚═════╝  ╚═════╝ 
            ___________________________________________________________________________________________

```
                                     
# d4u_migration_api

Table of Content

- [Description](#description)
- [Config File](#config-file)
- Data Mapping
<br />  

## Description
An OpenCart extention that helps your connect the E-Commerce to ERP(s) and fetch/migrate data.
Below you're going to find the steps how to migrate the data from client's ERP(s) to your OpenCart -
through /admin. The extentions is going to insert/update the products to the DB so you can
find and display it on your E-Commerce.
<br /><br /><br />

## Config File
Config File <code>/system/library/api4u/config.php</code> holds the API connectivity information and other defines
that our extension is using around.
<br />

> Unique API security code <code>define('API_CODE_0', '');</code> 

> Uncomment and use if there is secondary API <code>// define('API_CODE_1', '');</code> 

> ERP App name <code>define('API_APP_NAME', '');</code> 

> ERP DB Table <code>define('API_DB_ALIAS', '');</code>

> ERP DB User <code>define('API_DB_USERNAME', '');</code>

> ERP DB Password <code>define('API_DB_PASSWORD', '');</code>

> ERP Fetched Items Limitation (max 2000) <code>define('API_PACKAGE_SIZE', '');</code>

> In case API needs token for login <code>define('NEEDS_LOGIN_TOKEN', '');</code>



