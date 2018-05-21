<?php
namespace CAMOO\Controller;

use Cake\ORM\Locator\TableLocator;

class AppController
{
    public $controller = null;
    public $action = null;
    public $Flash = null;
    protected $oTemplate = null;
    protected $oLayout = null;
    protected $sTemplate = '%s/%s.tpl';
    protected $sTemplateDir = 'Templates';
    protected $sLayout = 'Templates/Layouts/default.tpl';
    public $request = null;
    private $http_version = '1.1';
    protected $tplData = [];
    protected $__sessionRaw = [\CAMOO\Http\Session::class, 'create'];

    public function __construct()
    {
    }

    private function __getSessionRaw()
    {
        return call_user_func($this->__sessionRaw);
    }

    /**
     * Initiliazes the controller engine
     *
     * @return void
     */
    public function initialize()
    {
        if ($this->oLayout === null) {
            $oTemplateLoader = new \Twig_Loader_Filesystem(APP.$this->sTemplateDir);
            $this->oLayout = new \Twig_Environment($oTemplateLoader, ['cache' => TMP.'cache'. DS . 'tpl']);
        }

        if ($this->oTemplate === null) {
            $this->oTemplate = $this->oLayout->load(sprintf($this->sTemplate, $this->controller, $this->action));
        }
        // @See https://github.com/auraphp/Aura.Session
        if (in_array(getEnv('REQUEST_METHOD'), ['DELETE', 'POST', 'PUT'])) {
                $csrf_value = $_POST['__csrf_Token'];
                $oCsrfToken = $this->__getSessionRaw()->getCsrfToken();
            if (! $oCsrfToken->isValid($csrf_value)) {
                throw \CAMOO\Exception\Exception("Request Blackholded.");
            }
        }
    }

    /**
     * Sets variables to templates
     *
     * @param mixed|string $varName
     * @param mixed $value
     * @return \CAMOO\Controller\AppController
     */
    public function set($varName, $value = null)
    {
        if ($varName !== null) {
            $this->tplData[$varName] = $value;
        } else {
            $this->tplData = array_merge($this->tplData, $varName);
        }
        $this->tplData['__csrf_Token'] = $this->request->csrf_Token;
        return $this;
    }

    /**
     * Renders the a template:w
     * @return void
     */
    public function render()
    {
        $this->beforeRender();
        print $this->oTemplate->render($this->tplData);
        $this->afterRender();
    }

    public function beforeRender()
    {
    }

    public function afterRender()
    {
        exit();
    }

    /**
     * @param  string $destination URL to redirect to
     */
    public function redirect($destination, $permanent = false)
    {
        $hServerParams = $this->request->getServerParams();
        if (mb_strpos($destination, '://') === false) {
            if (!isset($hServerParams['HTTPS'])
                || $hServerParams['HTTPS'] == 'off'
                || $hServerParams['HTTPS'] == ''
            ) {
                $protocol = 'http';
            } else {
                $protocol = 'https';
            }
            $destination = $protocol . '://' . $hServerParams['HTTP_HOST'] . $destination;
        }
        if ($permanent) {
            $code    = 301;
            $message = $code . ' Moved Permanently';
        } else {
            $code    = 302;
            $message = $code . ' Found';
        }
        header('HTTP/'.$hServerParams['SERVER_PROTOCOL'].' ' . $message, true, $code);
        header('Status: '  . $message, true, $code);
        header('Location: ' . $destination);
        exit();
    }

    protected function loadModel($sModel)
    {
        $this->{$sModel} = (new TableLocator())->get($sModel);
    }
}
