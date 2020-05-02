<?php

require_once CORE_PATH. 'include/function.php';
require_once CORE_PATH. 'config/constants.php';

use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Cache;
use Whoops\Run;
use Whoops\Handler\JsonResponseHandler;
use CAMOO\Utils\Configure;
use Cake\Datasource\ConnectionManager;
use CAMOO\Error\ErrorHandler;

Configure::load(CONFIG. 'app.php', false);
require_once CORE_PATH. 'include/tools.php';

$run     = new Run();
$handler = new ErrorHandler();
$handler->addDataTable('Camoo Framework', ['ErrorHandling' => \CAMOO\Error\ExceptionRenderer::class]);

$handler->setApplicationPaths([__FILE__]);
$run->pushHandler($handler);

if (\Whoops\Util\Misc::isAjaxRequest()) {
    $jsonHandler = new JsonResponseHandler();
    $jsonHandler->addTraceToOutput(true);
    $jsonHandler->setJsonApi(true);
    $run->pushHandler($jsonHandler);
}

$run->register();

ConnectionManager::setConfig('default', Configure::read('Database.default'));
