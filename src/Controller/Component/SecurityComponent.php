<?php
declare(strict_types=1);

namespace CAMOO\Controller\Component;

use CAMOO\Interfaces\ControllerInterface;
use CAMOO\Event\Event;
use CAMOO\Http\Session;
use CAMOO\Http\SessionSegment;
use CAMOO\Utils\Configure;
use CAMOO\Exception\Http\BadRequestException;
use CAMOO\Utils\Security;

/**
 * Class SecurityComponent
 * @author CamooSarl
 */
final class SecurityComponent extends BaseComponent
{
    private $_configKeys = [
        'unlockedActions'
    ];

    /** @var SessionSegment $csrfSessionSegment */
    public $csrfSessionSegment = null;

    /** @var string $_csrfSegment */
    private static $_csrfSegment='Aura\Session\CsrfToken';

    /** @var CAMOO\Http\ServerRequest $request */
    private $request;

    /** @var array $__sessionRaw */
    private $__sessionRaw = [Session::class, 'create'];

    /** @var null|string $csrf_Token */
    public $csrf_Token = null;

    public function __construct(?ControllerInterface $controller=null, array $config=[])
    {
        parent::__construct($controller, $config);
        $this->request = $this->getController()->request;
    }

    private function __getSessionRaw()
    {
        return call_user_func($this->__sessionRaw);
    }

    /**
     * @return SessionSegment
     */
    private function _getCsrfSegement($oSession) : SessionSegment
    {
        return new SessionSegment($oSession->segment(static::$_csrfSegment));
    }

    public function initialize(array $config) : void
    {
    }

    /**
     * @return bool
     */
    private function isUnlockedAction() : bool
    {
        $controller = $this->getController();
        if (($config = $controller->Security->getConfig()) && array_key_exists('unlockedActions', $config)) {
            return in_array($controller->action, $config['unlockedActions']);
        }
        return false;
    }

	/**
	 * @param Event $event
	 * @throw BadRequestException
	 * @return void
	 */
    public function wakeUp(Event $event) : void
    {
        $controller = $this->getController();

        $oSession = $this->__getSessionRaw();
        /** @var SessionSegment $oCsrfSgement */
        $oCsrfSgement = $this->_getCsrfSegement($oSession);

        ################## CSRF protection
        //	debug([$this->event]);
        // @See https://github.com/auraphp/Aura.Session
        if ($this->isUnlockedAction() === false && in_array($this->request->getMethod(), ['DELETE', 'POST', 'PUT', 'PATCH'])) {
            $csrfCreatedAt = (int) $oCsrfSgement->read('__csrf_created_at');
            $csrfTimeout = Configure::read('Security.csrf_lifetime') ?? 1800;

            $oCsrfToken = $oSession->getCsrfToken();
            $csrf_value = Security::satanizer($_POST['__csrf_Token']);
            if ((time() - $csrfCreatedAt) >  (int) $csrfTimeout || ! $oCsrfToken->isValid($csrf_value)) {
                throw new BadRequestException('Request Black-holed');
            }
            $hiddenSum = $oCsrfSgement->read('__csrf_checksum');
            if (!empty($hiddenSum)) {
                foreach ($hiddenSum as $field => $checkSumvalue) {
                    if (md5(Security::satanizer($_POST[$field])) !== $checkSumvalue) {
                        throw new BadRequestException('Value has been manipulated !');
                    }
                }
            }
        }

        if (Configure::read('Security.csrf_single_once') === true && $oCsrfSgement->check('__csrf_created_at')) {
            $oSession->getCsrfToken()->regenerateValue();
        }

        $this->csrf_Token = $oSession->getCsrfToken()->getValue();
        $oCsrfSgement->write('__csrf_created_at', time());
        $oCsrfSgement->delete('__csrf_checksum');
        $this->csrfSessionSegment = $oCsrfSgement;
        ################## CSRF protection END
    }

    /**
     * @return array
     */
    public function implementedEvents()
    {
        return [
            'AppController.wakeUp' => 'wakeUp',
        ];
    }
}
