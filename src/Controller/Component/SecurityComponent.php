<?php

declare(strict_types=1);

namespace CAMOO\Controller\Component;

use CAMOO\Event\Event;
use CAMOO\Exception\Http\BadRequestException;
use CAMOO\Exception\Http\ForbiddenException;
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
    private static $_csrfSegment = 'Aura\Session\CsrfToken';

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
        if ($this->isUnlockedAction() === false && in_array(
            $this->request->getMethod(),
            ['DELETE', 'POST', 'PUT', 'PATCH']
        )) {
            $csrfCreatedAt = (int)$oCsrfSegment->read('__csrf_created_at');
            $csrfTimeout = Configure::read('Security.csrf_lifetime') ?? 1800;

            // CHECK TO ENSURE REFERRER URL IS ON THIS DOMAIN
            if (strpos($this->request->getEnv('HTTP_REFERER'), $this->request->getEnv('HTTP_HOST')) === false) {
                throw new ForbiddenException('Bad Referrer !');
            }

            if (!array_key_exists('__csrf_Token', $_POST)) {
                throw new BadRequestException('__csrf_Token is missing !');
            }

            $oCsrfToken = $oSession->getCsrfToken();
            $csrf_value = Security::satanizer($_POST['__csrf_Token']);
            if ((time() - $csrfCreatedAt) > (int)$csrfTimeout || !$oCsrfToken->isValid($csrf_value)) {
                throw new BadRequestException('Request Black-holed');
            }
            $hiddenSum = $oCsrfSegment->read('__csrf_checksum');
            if (!empty($hiddenSum)) {
                foreach ($hiddenSum as $field => $checkSumvalue) {
                    if (md5(Security::satanizer($_POST[$field])) !== $checkSumvalue) {
                        throw new BadRequestException('Value has been Manipulated !');
                    }
                }
            }
        }

        if (Configure::read('Security.csrf_single_once') === true &&
            $oCsrfSegment->check('__csrf_created_at')) {
            $oSession->getCsrfToken()->regenerateValue();
        }

        $this->csrf_Token = $oSession->getCsrfToken()->getValue();
        $oCsrfSegment->write('__csrf_created_at', time());
        $oCsrfSegment->delete('__csrf_checksum');
        $this->csrfSessionSegment = $oCsrfSegment;
        ################## CSRF protection END
    }

    /** @return array */
    public function implementedEvents()
    {
        return [
            'AppController.wakeUp' => 'wakeUp',
        ];
    }

    private function _getCsrfSegment($oSession): SessionSegment
    {
        return new SessionSegment($oSession->segment(self::$_csrfSegment));
    }

    private function isUnlockedAction(): bool
    {
        $controller = $this->getController();
        if (($config = $controller->Security->getConfig()) && array_key_exists('unlockedActions', $config)) {
            return in_array($controller->action, $config['unlockedActions']);
        }

        return false;
    }
}
