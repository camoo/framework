<?php

/**
 * Test runner bootstrap.
 */

use CAMOO\Utils\Configure;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__) . DS);
}
if (!defined('CORE_PATH')) {
    define('CORE_PATH', ROOT);
}
if (!defined('APP')) {
    define('APP', ROOT . 'src' . DS);
}
if (!defined('TMP')) {
    define('TMP', sys_get_temp_dir() . DS);
}
if (!defined('CACHE')) {
    define('CACHE', TMP . 'cache' . DS);
}
if (!defined('LOGS')) {
    define('LOGS', TMP . 'logs' . DS);
}
if (!defined('CONFIG')) {
    define('CONFIG', ROOT . 'config' . DS);
}

@mkdir(CACHE, 0777, true);
@mkdir(LOGS, 0777, true);

require_once ROOT . 'vendor/autoload.php';
require_once ROOT . 'include/function.php';
require_once ROOT . 'config/constants.php';

Configure::write('Cache._camoo_hosting_conf', ['path' => CACHE]);
Configure::write('Cache.camoo_di', ['path' => CACHE . 'persistent/di/']);
Configure::write('Session.name', 'TESTSESS');
Configure::write('Session.cookie', ['expire' => 3600, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true]);
