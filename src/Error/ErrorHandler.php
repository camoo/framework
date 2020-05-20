<?php
declare(strict_types=1);

namespace CAMOO\Error;

use Whoops\Handler\PrettyPageHandler;
use Whoops\Handler\Handler;
use CAMOO\Utils\Configure;
use Whoops\Exception\Inspector;
use Error;
use GuzzleHttp\Psr7;
use CAMOO\Http\ServerRequest;

/**
 * Class ErrorHandler
 * @author CamooSarl
 */
final class ErrorHandler extends PrettyPageHandler
{
    /** @var ExceptionRenderer $exceptionRenderer */
    private $exceptionRenderer;

    /** @var Inspector $inspector */
    private $inspector;

    /** @var null|ServerRequest $request */
    private $request;

    public function __construct()
    {
        parent::__construct();
        if (!$this->isCli()) {
            $this->request = new ServerRequest(Psr7\ServerRequest::fromGlobals());
        }
    }

    private function isCli() : bool
    {
        return PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
    }

    public function handle()
    {
        $this->inspector = $this->getInspector();
        $exception = $this->inspector->getException();
        $className = get_class($exception);
        $errorTrace = $exception->getMessage() . "\n";
        $errorTrace .= $exception->getTraceAsString();
        $errorTrace .= $this->_getRequestMessage();
        error_log($errorTrace, 3, LOGS. 'error.log');

        if (Configure::read('debug')) {
            parent::handle();
            return Handler::QUIT;
        }
    
        if (null !== $this->request) {
            $this->exceptionRenderer = new ExceptionRenderer($exception, $this->request);
            $this->exceptionRenderer->render();
        }

        return Handler::QUIT;
    }

    protected function _getRequestMessage() : ?string
    {
        if ($this->isCli()) {
            return null;
        }
        $message = "\nRequest URL: " . $this->request->getRequestTarget();

        $referer = $this->request->getReferer();
        if ($referer) {
            $message .= "\nReferer URL: " . $referer;
        }
        $clientIp = $this->request->getRemoteIp();
        if ($clientIp && $clientIp !== '::1') {
            $message .= "\nClient IP: " . $clientIp;
        }

        return $message . "\n";
    }
}
