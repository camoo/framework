<?php

require_once CORE_PATH. 'include/function.php';
require_once CORE_PATH. 'config/constants.php';

use Cake\Cache\Engine\FileEngine;
use Cake\Cache\Cache;
#use RuntimeException;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\Handler\JsonResponseHandler;
use CAMOO\Utils\Configure;
use Cake\Datasource\ConnectionManager;

Configure::load(CONFIG. 'app.php', false);
require_once CORE_PATH. 'include/tools.php';

$run     = new Run();
$handler = new PrettyPageHandler();
$handler->addDataTable('Camoo Framework', ['ErrorHandling' => '']);

$handler->setApplicationPaths([__FILE__]);
$run->pushHandler($handler);

if (\Whoops\Util\Misc::isAjaxRequest()) {
    $jsonHandler = new JsonResponseHandler();
    $jsonHandler->addTraceToOutput(true);
    $jsonHandler->setJsonApi(true);
    // And push it into the stack:
    $run->pushHandler($jsonHandler);
}

/*
$handler->addDataTableCallback('Details', function(\Whoops\Exception\Inspector $inspector) {
    $data = array();
    $exception = $inspector->getException();
    if ($exception instanceof SomeSpecificException) {
        $data['Important exception data'] = $exception->getSomeSpecificData();
    }
    $data['Exception class'] = get_class($exception);
    $data['Exception code'] = $exception->getCode();
    return $data;
});

////$run->pushHandler($handler);
// Example: tag all frames inside a function with their function name
$run->pushHandler(function ($exception, $inspector, $run) {

    $inspector->getFrames()->map(function ($frame) {

        if ($function = $frame->getFunction()) {
            $frame->addComment("This frame is within function '$function'", 'cpt-obvious');
        }

        return $frame;
    });

});
 */

$run->register();

ConnectionManager::setConfig('default', Configure::read('Database.default'));
