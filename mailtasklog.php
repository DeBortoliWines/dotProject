<?php
require_once 'base.php';
require_once DP_BASE_DIR.'/includes/config.php';
require_once DP_BASE_DIR.'/includes/main_functions.php';
require_once DP_BASE_DIR.'/includes/db_connect.php';
require_once DP_BASE_DIR.'/classes/ui.class.php';
require_once DP_BASE_DIR.'/classes/mail2log.class.php';
require_once DP_BASE_DIR.'/classes/query.class.php';
$AppUI = new CAppUI;
$AppUI->setUserLocale();
$perms =& $AppUI->acl();
require_once($AppUI->getLibraryClass('google-api-php-client-2.2.1/vendor/autoload'));
require_once($AppUI->getModuleClass('tasks'));

// if (php_sapi_name() != 'cli') {
//     throw new Exception('This application must be run on the command line.');
// }

$mailLog = new Mail2Log();
$client = $mailLog->getClient();
$service = $mailLog->getService($client);
$mailLog->processMessage($service, 5);