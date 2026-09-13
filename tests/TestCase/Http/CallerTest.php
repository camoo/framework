<?php

namespace CAMOO\Test\TestCase\Http;

use CAMOO\Controller\AppController;
use CAMOO\Http\Caller;
use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class TestCallerController extends AppController
{
    public function overview(): string
    {
        return 'Overview Result';
    }

    protected function camooExit(): void
    {
    }
}

#[CoversClass(Caller::class)]
class CallerTest extends TestCase
{
    private string $configDir;

    protected function setUp(): void
    {
        $this->configDir = TMP . 'caller_test_config_' . uniqid();
        mkdir($this->configDir, 0777, true);

        file_put_contents($this->configDir . '/bootstrap.php', '<?php ');

        $routeContent = <<<'PHP'
<?php
use function FastRoute\simpleDispatcher;
use FastRoute\RouteCollector;

$dispatcher = simpleDispatcher(function (RouteCollector $r) {
    $r->addRoute('GET', '/test-page', ['controller' => 'Pages', 'action' => 'overview']);
});

return [$dispatcher];
PHP;
        file_put_contents($this->configDir . '/route.php', $routeContent);

        putenv('REQUEST_METHOD=GET');
        putenv('REQUEST_URI=/test-page');
    }

    protected function tearDown(): void
    {
        @unlink($this->configDir . '/bootstrap.php');
        @unlink($this->configDir . '/route.php');
        @rmdir($this->configDir);
    }

    public function testCallerInitializationAndRoute(): void
    {
        $caller = new Caller($this->configDir);
        $this->assertSame('/test-page', $caller->uri);
    }

    public function testCallerRouteNotFound(): void
    {
        putenv('REQUEST_URI=/unknown/action-name');
        $caller = new Caller($this->configDir);
        $this->assertSame('actionName', $caller->action);
    }
}
