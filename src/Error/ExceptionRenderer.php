<?php

declare(strict_types=1);

namespace CAMOO\Error;

use CAMOO\Controller\ErrorController;
use Camoo\Http\Curl\Domain\Entity\Stream;
use Camoo\Http\Curl\Infrastructure\Response;
use CAMOO\Http\ServerRequest;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Interfaces\HttpExceptionInterface;
use CAMOO\Utils\Configure;
use Error;
use Throwable;

/**
 * Class ExceptionRenderer
 *
 * @author CamooSarl
 */
final class ExceptionRenderer
{
    public const string MSG_NOT_FOUND = 'Page Not Found';

    public const string MSG_INTERNAL_ERR = 'An Internal Error Has Occurred';

    /**
     * Controller instance.
     */
    public ErrorController|ControllerInterface $controller;

    /**
     * Creates the controller to perform rendering on the error response.
     *
     * @param Error $error Exception.
     */
    public function __construct(public Error|Throwable $error, private readonly ServerRequest $request)
    {
        $this->controller = $this->_getController();
    }

    /** Renders the response for the exception. */
    public function render(): mixed
    {
        $exception = $this->error;
        $controller = $this->controller;
        $code = $exception->getCode() ?? 404;
        $message = $this->_getMessage();
        if ($code === 0 && md5(self::MSG_NOT_FOUND) === md5($message)) {
            $code = 404;
        }
        $controller->set('code', $code);
        $controller->set('message', $message);
        http_response_code((int)$code);

        return call_user_func_array([$controller, 'overview'], []);
    }

    /** Get the controller instance to handle the exception. */
    protected function _getController(): ControllerInterface
    {
        $oController = new ErrorController();
        $oController->request = $this->request;
        $oController->action = 'overview';
        $oController->controller = 'Error';
        $oController->setResponse(new Response(body: new Stream('')));
        $oController->wakeUpController();

        return $oController;
    }

    /**
     * Get error message.
     *
     * @return string Error message
     */
    protected function _getMessage(): string
    {
        $exception = $this->error;
        $message = $exception->getMessage();
        $code = $exception->getCode();

        if (
            !Configure::read('debug') &&
            !($exception instanceof HttpExceptionInterface)
        ) {
            if ($code < 500) {
                $message = self::MSG_NOT_FOUND;
            } else {
                $message = self::MSG_INTERNAL_ERR;
            }
        }

        return $message;
    }
}
