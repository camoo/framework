<?php
declare(strict_types=1);

namespace CAMOO\Error;

use CAMOO\Controller\ErrorController;
use CAMOO\Http\Response;
use CAMOO\Http\ServerRequest;
use Error;
use CAMOO\Interfaces\HttpExceptionInterface;
use CAMOO\Utils\Configure;
use CAMOO\Interfaces\ControllerInterface;

/**
 * Class ExceptionRenderer
 * @author CamooSarl
 */
final class ExceptionRenderer
{
    const MSG_NOT_FOUND = 'Page Not Found';
    const MSG_INTERNAL_ERR = 'An Internal Error Has Occurred';
    /**
     * The exception being handled.
     *
     * @var Error
     */
    public $error;

    /**
     * Controller instance.
     *
     * @var \CAMOO\Controller\ErrorController
     */
    public $controller;

    /** @var ServerRequest $request */
    private $request;

    /**
     * Creates the controller to perform rendering on the error response.
     *
     * @param Error $exception Exception.
     */
    public function __construct($exception, ServerRequest $request)
    {
        $this->request = $request;
        $this->error = $exception;
        $this->controller = $this->_getController();
    }

    /**
     * Get the controller instance to handle the exception.
     *
     * @return \CAMOO\Controller\ErrorController
     */
    protected function _getController() : ControllerInterface
    {
        $oController = new ErrorController();
        $oController->request = $this->request;
        $oController->action = 'overview';
        $oController->controller = 'Error';
        $oController->setResponse(new Response());
        $oController->wakeUpController();

        return $oController;
    }

    /**
     * Renders the response for the exception.
     */
    public function render()
    {
        $exception = $this->error;
        $controller = $this->controller;
        $code = $exception->getCode() ?? 404;
        $message = $this->_getMessage();
        if ($code === 0 && md5(static::MSG_NOT_FOUND) === md5($message)) {
            $code = 404;
        }
        $controller->set('code', $code);
        $controller->set('message', $message);
        $headerResponse = '%d %s';
        header('HTTP/1.1 '. sprintf($headerResponse, $code, $message));
        return call_user_func_array([$controller, 'overview'], []);
    }

    /**
     * Get error message.
     *
     * @return string Error message
     */
    protected function _getMessage() : string
    {
        $exception = $this->error;
        $message = $exception->getMessage();
        $code = $exception->getCode();

        if (!Configure::read('debug') &&
            !($exception instanceof HttpExceptionInterface)
        ) {
            if ($code < 500) {
                $message = static::MSG_NOT_FOUND;
            } else {
                $message = static::MSG_INTERNAL_ERR;
            }
        }

        return $message;
    }
}
