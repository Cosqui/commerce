<?php
//
// +----------------------------------------------------------------------+
// |Zen Cart Open Source E-commerce                                       |
// +----------------------------------------------------------------------+
// | Copyright (c) 2004 The Zen Cart developers                           |
// |                                                                      |
// | http://www.zen-cart.com/index.php                                    |
// |                                                                      |
// | Portions Copyright (c) 2003 osCommerce                               |
// +----------------------------------------------------------------------+
// | This source file is subject to version 2.0 of the GPL license,       |
// | that is bundled with this package in the file LICENSE, and is        |
// | available through the world-wide-web at the following url:           |
// | http://www.zen-cart.com/license/2_0.txt.                             |
// | If you did not receive a copy of the Zen Cart license and are unable |
// | to obtain it through the world-wide-web, please send a note to       |
// | license@zen-cart.com so we can mail you a copy immediately.          |
// +----------------------------------------------------------------------+
//



require_once( BITCOMMERCE_PKG_PATH.'includes/configure.php' );

  define('HTTP_CATALOG_SERVER', 'http://'.$_SERVER['HTTP_HOST'] );
  define('HTTPS_CATALOG_SERVER', 'https://'.$_SERVER['HTTP_HOST'] );

  // secure webserver for catalog module and/or admin areas?
  define('ENABLE_SSL_CATALOG', 'false');
  define('ENABLE_SSL_ADMIN', 'false');

  define('DIR_WS_ADMIN', BITCOMMERCE_PKG_URL.'admin/');
  define('DIR_WS_HTTPS_ADMIN', BITCOMMERCE_PKG_URL.'admin/');

  define('DIR_WS_CATALOG_TEMPLATE', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'includes/templates/');

  define('DIR_WS_CATALOG_LANGUAGES', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'includes/languages/');

  define('DIR_FS_ADMIN', '/a1/viovio/live/commerce/admin/');

  define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
  define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');
  define('DIR_FS_CATALOG_TEMPLATES', DIR_FS_CATALOG . 'includes/templates/');
  define('DIR_FS_CATALOG_BLOCKS', DIR_FS_CATALOG . 'includes/blocks/');
  define('DIR_FS_CATALOG_BOXES', DIR_FS_CATALOG . 'includes/boxes/');
  define('DIR_FS_BACKUP', DIR_FS_ADMIN . 'backups/');
  define('DIR_FS_FILE_MANAGER_ROOT', BITCOMMERCE_PKG_PATH); // path to starting directory of the file manager

  define('DIR_FS_ADMIN_INCLUDES', 'includes/');

  mkdir_p( DIR_FS_CATALOG_IMAGES );
/*

// Define the webserver and path parameters
  // Main webserver: eg, http://localhost - should not be empty for productive servers
  define('HTTP_SERVER', 'http://www.dev.viovio.com');
  // Secure webserver: eg, https://localhost - should not be empty for productive servers
  define('HTTPS_SERVER', 'https://www.dev.viovio.com'); // eg, https://localhost
  define('HTTP_CATALOG_SERVER', 'http://www.dev.viovio.com');
  define('HTTPS_CATALOG_SERVER', 'https://www.dev.viovio.com');

  // secure webserver for catalog module and/or admin areas?
  define('ENABLE_SSL_CATALOG', 'false');
  define('ENABLE_SSL_ADMIN', 'false');

// NOTE: be sure to leave the trailing '/' at the end of these lines if you make changes!
// * DIR_WS_* = Webserver directories (virtual/URL)
  // these paths are relative to top of your webspace ... (ie: under the public_html or httpdocs folder)
  define('DIR_WS_ADMIN', '/commerce/admin/');
  define('DIR_WS_CATALOG', '/commerce/');
  define('DIR_WS_HTTPS_ADMIN', '/commerce/admin/');
  define('DIR_WS_HTTPS_CATALOG', '/commerce/');

  define('DIR_WS_IMAGES', 'images/');
  define('DIR_WS_ICONS', DIR_WS_IMAGES . 'icons/');
  define('DIR_WS_CATALOG_IMAGES', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'images/');
  define('DIR_WS_CATALOG_TEMPLATE', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'includes/templates/');
  define('DIR_WS_BOXES', DIR_WS_INCLUDES . 'boxes/');
  define('DIR_WS_FUNCTIONS', DIR_WS_INCLUDES . 'functions/');
  define('DIR_WS_CLASSES', DIR_WS_INCLUDES . 'classes/');
  define('DIR_WS_MODULES', DIR_WS_INCLUDES . 'modules/');
  define('DIR_WS_LANGUAGES', DIR_WS_INCLUDES . 'languages/');
  define('DIR_WS_CATALOG_LANGUAGES', HTTP_CATALOG_SERVER . DIR_WS_CATALOG . 'includes/languages/');
  define('DIR_WS_BLOCKS', DIR_WS_INCLUDES . 'blocks/');

// * DIR_FS_* = Filesystem directories (local/physical)
  //the following path is a COMPLETE path to your Zen Cart files. eg: /var/www/vhost/accountname/public_html/store/
  define('DIR_FS_CATALOG', BITCOMMERCE_PKG_PATH );
  define('DIR_FS_ADMIN', DIR_FS_CATALOG.'/admin/');

  define('DIR_FS_CATALOG_LANGUAGES', DIR_FS_CATALOG . 'includes/languages/');
  define('DIR_FS_CATALOG_IMAGES', DIR_FS_CATALOG . 'images/');
  define('DIR_FS_CATALOG_MODULES', DIR_FS_CATALOG . 'includes/modules/');
  define('DIR_FS_CATALOG_TEMPLATES', DIR_FS_CATALOG . 'includes/templates/');
  define('DIR_FS_CATALOG_BLOCKS', DIR_FS_CATALOG . 'includes/blocks/');
  define('DIR_FS_CATALOG_BOXES', DIR_FS_CATALOG . 'includes/boxes/');
  define('DIR_FS_BACKUP', DIR_FS_ADMIN . 'backups/');
  define('DIR_FS_EMAIL_TEMPLATES', DIR_FS_CATALOG . 'email/');
  define('DIR_FS_FILE_MANAGER_ROOT', BITCOMMERCE_PKG_PATH); // path to starting directory of the file manager

// define our database connection
  define('DB_TYPE', 'mysql');
  define('DB_SERVER', 'localhost'); // eg, localhost - should not be empty
  define('DB_SERVER_USERNAME', 'cfowler');
  define('DB_SERVER_PASSWORD', 'asdf');
  define('DB_DATABASE', 'zencart');
  define('USE_PCONNECT', 'false'); // use persistent connections?
  define('STORE_SESSIONS', 'db'); // leave empty '' for default handler or set to 'db'

  // The next 2 "defines" are for SQL cache support.
  // For SQL_CACHE_METHOD, you can select from:  none, database, or file
  // If you choose "file", then you need to set the DIR_FS_SQL_CACHE to a directory where your apache
  // or webserver user has write privileges (chmod 666 or 777). We recommend using the "cache" folder inside the Zen Cart folder
  // ie: /path/to/your/webspace/public_html/zen/cache   -- leave no trailing slash
  define('SQL_CACHE_METHOD', 'none');
  define('DIR_FS_SQL_CACHE', TEMP_PKG_PATH.'zencache');
*/
?>
