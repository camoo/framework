<?php
declare(strict_types=1);

require_once CORE_PATH. 'include/function.php';
require_once CORE_PATH. 'config/constants.php';

use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Cache;
use Whoops\Run;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use CAMOO\Utils\Configure;
use CAMOO\Utils\Utility;
use Cake\Datasource\ConnectionManager;
use CAMOO\Error\ErrorHandler;
use CAMOO\Error\ExceptionRenderer;
use Whoops\Util\Misc;

Configure::load(CONFIG. 'app.php', false);
require_once CORE_PATH. 'include/tools.php';

$run     = new Run();
$handler = new ErrorHandler();
$handler->addDataTable('Camoo Framework', ['ErrorHandling' => ExceptionRenderer::class]);

$handler->setApplicationPaths([__FILE__]);
$run->pushHandler($handler);

if (Misc::isAjaxRequest()) {
    $jsonHandler = new JsonResponseHandler();
    $jsonHandler->addTraceToOutput(true);
    $jsonHandler->setJsonApi(true);
    $run->pushHandler($jsonHandler);
}

// CLI
if (Utility::isCli() === true) {
    $cliHandler = new PlainTextHandler();
    $cliHandler->addTraceToOutput(true);
    $run->pushHandler($cliHandler);
}

$run->register();

if (Configure::check('Database') === true) {
    ConnectionManager::setConfig('default', Configure::read('Database.default'));
}
