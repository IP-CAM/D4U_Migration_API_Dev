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

<h3>Table of Content</h3>

- [Description](#description)
- [Config File](#config-file)
- [Data Mapping](#data-mapping)
<br />  

## Description
An OpenCart extention that helps your connect the E-Commerce to ERP(s) and fetch/migrate data.
Below you're going to find the steps how to migrate the data from client's ERP(s) to your OpenCart -
through /admin. The extentions is going to insert/update the products to the DB so you can
find and display it on your E-Commerce.
<br /><br /><br />

## Config File
Config File <code>/system/library/api4u/config.php</code> holds the API connectivity information and other defines
that our extension is using around, in functions and statements.
<br />

> Unique API security code <code>define('API_CODE_0', '');</code> 

> Uncomment and use if it's multi-store <code>// define('API_CODE_1', '');</code> 

> ERP App name <code>define('API_APP_NAME', '');</code> 

> ERP DB Table <code>define('API_DB_ALIAS', '');</code>

> ERP DB User <code>define('API_DB_USERNAME', '');</code>

> ERP DB Password <code>define('API_DB_PASSWORD', '');</code>

> ERP Fetched Items Limitation (max 2000) <code>define('API_PACKAGE_SIZE', '');</code>

> In case API needs token for login <code>define('NEEDS_LOGIN_TOKEN', '');</code>

> In case API works with POST (true) or GET (false) <code>define('IS_POST_OR_GET', false);</code>

> API URL and Port connection (i.e. `'http://ip:port'`) <code>define('API_URL', '');</code>

> Enable/Disable JSON Decode for returned Products, if needed or broke something <code>define('JSON_DECODE_PRODUCT', true);</code>

<br />

## Data Mapping
Each ERP API has it's own data fields. These fields mostly are translated - getting names close to client. In order to fetch and
store the data we have to translate it also in OpenCart's 'language'. This kind of action it gets into `/system/library/api4u/product.php` 
at `integrateProduct()` function, i.e. if we have just the . 

1. We have to find/analyze the values that client and ERP returns, on JSON/Array/List at the API we GET/POST through.
2. Then we can see the data structure to define which name/field has values stored at, i.e. `"Data":[{values}]` (here is "Data") or i.e. `"Data":{"Items":[{values}]}` / `"Data":{"AlterCodes":[{values}]}` (here is "Items"/"AlterCodes") etc.
3. Next we use the field/name that includes the values/data and replace it on `"entitycode" => "field/name",` inside `$data_array['get_products']` (i.e. `"entitycode" => "AlterCodes",`.
4. At last we declare the pointed array path of data onto `PRODUCTS_FIELD_NAME_IN_JSON` (i.e. `define('PRODUCTS_FIELD_NAME_IN_JSON', '$response['Data']['AlterCodes']');`), so it can check the data and proceed to the integration if exists.
5. In the end you have to map the variables (i.e. `$special_price`, `$price`, etc) with the value of the relative Product's Array/JSON values (i.e. `$price = $value['RETAILPRICE']`, `$price = $value['INITIALRTLPRICE']`)
6. Most important Mapping variables to be filled with data, for a completed migration:

  | Variable | Data Value Expect |
  | --- | --- |
  | $sku | Product's SKU |
  | $upc | Product's UPC |
  | $ean | Product's EAN |
  | $jan | Product's JAN |
  | $isbn | Product's ISBN |
  | $mpn | Product's MPN |
  | $location | Product's AREA where it has/not extra Delivery costs |
  | $quantity | Product's Stock Quantity |
  | $stock_status_id | Depending on Product's Stock Quantity decide the Availability Text/Label (by ID) |
  | $image | Product's Image file Name |
  | $manufacturer_id | Product's Manufacturer based on it's ID |
  | $shipping | Product's Shipping availability, if it's available or not |
  | $price | Product's Price, will be the Discount Price if exists otherwise just the Price |
  | $special_price | Product's Price, will be the previous Price before the discount otherwise zero |
  | $tax_class_id | Product's Tax/VAT class |
  | $name | Product's Title/Name |
  | $description | Product's Description/Details |





