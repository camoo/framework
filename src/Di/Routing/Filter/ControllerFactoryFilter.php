<?php

declare(strict_types=1);

namespace CAMOO\Di\Routing\Filter;

use Cake\Routing\Filter\ControllerFactoryFilter as ParentFactory;
use CAMOO\Di\Application;
use CAMOO\Di\CamooDi;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A dispatcher filter that builds the controller to dispatch
 * in the request.
 *
 * This filter resolves the request parameters into a controller
 * instance and attaches it to the event object.
 */
class ControllerFactoryFilter extends ParentFactory
{
    /** Priority is set high to allow other filters to be called first. */
    protected int $priority = 50;

    /**
     * Get controller to use, either plugin controller or application controller
     *
     * @param ServerRequest|ServerRequestInterface $request  Request object
     * @param Response|ResponseInterface           $response Response for the controller.
     *
     * @throws Exception
     *
     * @return Controller name of controller if not loaded, or object if loaded
     */
    protected function _getController($request, $response)
    {
        $controller = parent::_getController($request, $response);

        $controllerClass = get_class($controller);

        $controllers = new ControllerCollection();
        $application = new Application();
        $application->controllers($controllers);

        if (!in_array($controllerClass, $controllers->getIterator()->getArrayCopy())) {
            return $controller;
        }

        /** @var Controller $instance */
        $instance = CamooDi::get($controllerClass);

        $instance->name = $controller->name;
        $instance->plugin = $controller->plugin;
        $instance->components($controller->components());
        $instance->modelClass = $controller->modelClass;
        $instance->RequestHandler = $controller->RequestHandler;
        $instance->Security = $controller->Security;
        $instance->LoginUserPermission = $controller->LoginUserPermission;
        $instance->International = $controller->International;
        $instance->Flash = $controller->Flash;
        $instance->setRequest($controller->request);
        $instance->response = $controller->response;

        return $instance;
    }
}
