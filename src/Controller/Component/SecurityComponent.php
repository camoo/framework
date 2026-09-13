<?php

declare(strict_types=1);

namespace CAMOO\Controller\Component;

use CAMOO\Event\Event;
use CAMOO\Exception\Http\BadRequestException;
use CAMOO\Http\ServerRequest;
use CAMOO\Http\Session;
use CAMOO\Http\SessionSegment;
use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Utils\Configure;
use CAMOO\Utils\Security;

/**
 * Class SecurityComponent
 *
 * @author CamooSarl
 */
final class SecurityComponent extends BaseComponent
{
    /** @var SessionSegment $csrfSessionSegment */
    public $csrfSessionSegment = null;

    /** @var string|null $csrf_Token */
    public $csrf_Token = null;

    /** @var array $_configKeys */
    private $_configKeys = [
        'unlockedActions',
    ];

    /** @var string $_csrfSegment */
    private static $_csrfSegment = \Aura\Session\CsrfToken::class;

    /** @var ServerRequest $request */
    private $request;

    /** @var array $__sessionRaw */
    private $__sessionRaw = [Session::class, 'create'];

    public function __construct(?ControllerInterface $controller = null, array $config = [])
    {
        parent::__construct($controller, $config);
        $this->request = $this->getController()->request;
    }

    private function __getSessionRaw()
    {
        return call_user_func($this->__sessionRaw);
    }

    public function initialize(array $config = []): void
    {
    }

    /** @throw BadRequestException */
    public function wakeUp(Event $event): void
    {
        // $controller = $this->getController();

        $oSession = $this->__getSessionRaw();
        $oCsrfSegment = $this->_getCsrfSegment($oSession);

        ################## CSRF protection
        // @See https://github.com/auraphp/Aura.Session
        if (
            in_array($this->request->getMethod(), ['DELETE', 'POST', 'PUT', 'PATCH'])
        ) {
            $csrfCreatedAt = (int)$oCsrfSegment->read('__csrf_created_at');
            $csrfTimeout = Configure::read('Security.csrf_lifetime') ?? 1800;

            $csrfValue = $this->request->getRawData('__csrf_Token');
            if (!is_string($csrfValue) || $csrfValue === '') {
                throw new BadRequestException('__csrf_Token is missing !');
            }

            $oCsrfToken = $oSession->getCsrfToken();
            if ((time() - $csrfCreatedAt) > (int)$csrfTimeout || !$oCsrfToken->isValid($csrfValue)) {
                throw new BadRequestException('Request Black-holed');
            }
            $hiddenSum = $oCsrfSegment->read('__csrf_checksum');
            if (!empty($hiddenSum)) {
                foreach ($hiddenSum as $field => $checkSumvalue) {
                    $fieldValue = $this->request->getRawData($field);
                    if (!is_scalar($fieldValue) || !hash_equals(
                        (string)$checkSumvalue,
                        hash('sha256', Security::satanizer((string)$fieldValue)),
                    )) {
                        throw new BadRequestException('Value has been Manipulated !');
                    }
                }
            }
        }

        if (
            Configure::read('Security.csrf_single_once') === true &&
            $oCsrfSegment->check('__csrf_created_at')
        ) {
            $oSession->getCsrfToken()->regenerateValue();
        }

        $this->csrf_Token = $oSession->getCsrfToken()->getValue();
        $oCsrfSegment->write('__csrf_created_at', time());
        $oCsrfSegment->delete('__csrf_checksum');
        $this->csrfSessionSegment = $oCsrfSegment;
        ################## CSRF protection END
    }

    /** @return array */
    public function implementedEvents(): array
    {
        return [
            'AppController.wakeUp' => 'wakeUp',
        ];
    }

    private function _getCsrfSegment($oSession): SessionSegment
    {
        return new SessionSegment($oSession->segment(self::$_csrfSegment));
    }

}
