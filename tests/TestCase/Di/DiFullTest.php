<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Di;

use CAMOO\Controller\ErrorController;
use CAMOO\Di\Annotation\Assisted;
use CAMOO\Di\Application as DiApplication;
use CAMOO\Di\Cache\CacheAdapter;
use CAMOO\Di\CamooDi;
use CAMOO\Di\Container;
use CAMOO\Di\Interceptor\AssistedInterceptor;
use CAMOO\Di\Module\AssistedModule;
use CAMOO\Di\Module\DefaultModule;
use CAMOO\Di\Module\ModuleCollection;
use CAMOO\Di\Routing\Filter\ControllerFactoryFilter;
use CAMOO\Di\UnTargetedBind;
use CAMOO\Utils\Configure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

class SampleService
{
    public function getValue(): string
    {
        return 'sample_value';
    }
}

class SampleAssistedService
{
    #[Assisted]
    public function execute(SampleService $service): string
    {
        return $service->getValue();
    }
}

class Application
{
    public bool $called = false;

    public function dependencyInjectionModules(ModuleCollection $modules): void
    {
        $this->called = true;
    }
}

#[CoversClass(CamooDi::class)]
#[CoversClass(Container::class)]
#[CoversClass(CacheAdapter::class)]
#[CoversClass(AssistedInterceptor::class)]
#[CoversClass(AssistedModule::class)]
#[CoversClass(DefaultModule::class)]
#[CoversClass(ControllerFactoryFilter::class)]
#[CoversClass(DiApplication::class)]
#[CoversClass(UnTargetedBind::class)]
class DiFullTest extends TestCase
{
    public function setUp(): void
    {
        Configure::write('_camoo_hosting_conf', ['path' => CACHE]);
    }

    public function testCamooDiCreateAndContainer(): void
    {
        $injector = CamooDi::create();
        $this->assertInstanceOf(Injector::class, $injector);
        $this->assertSame($injector, CamooDi::container());
    }

    public function testCamooDiCreateWithCallable(): void
    {
        $injector = CamooDi::create(fn () => []);
        $this->assertInstanceOf(Injector::class, $injector);
    }

    public function testCamooDiGet(): void
    {
        CamooDi::create();
        $service = CamooDi::get(SampleService::class);
        $this->assertInstanceOf(SampleService::class, $service);
        $this->assertSame('sample_value', $service->getValue());
    }

    public function testCamooDiHelperFunctionGet(): void
    {
        CamooDi::create();
        $container = di();
        $this->assertInstanceOf(Container::class, $container);
        $this->assertTrue($container->has(SampleService::class));

        $service = di()->get(SampleService::class);
        $this->assertInstanceOf(SampleService::class, $service);
        $this->assertSame('sample_value', $service->getValue());

        $camooService = camoo_di()->get(SampleService::class);
        $this->assertInstanceOf(SampleService::class, $camooService);
    }

    public function testCamooDiHelperFunctionShorthand(): void
    {
        CamooDi::create();
        $service = di(SampleService::class);
        $this->assertInstanceOf(SampleService::class, $service);
        $this->assertSame('sample_value', $service->getValue());

        $camooService = camoo_di(SampleService::class);
        $this->assertInstanceOf(SampleService::class, $camooService);
    }

    public function testContainerMagicCall(): void
    {
        CamooDi::create();
        $container = di();
        $service = $container->getInstance(SampleService::class);
        $this->assertInstanceOf(SampleService::class, $service);
    }

    public function testCacheAdapterAllMethods(): void
    {
        $adapter = new CacheAdapter('_camoo_hosting_conf');
        $this->assertInstanceOf(CacheAdapter::class, $adapter);

        $refMethod = new \ReflectionMethod($adapter, 'doSave');
        $refMethod->setAccessible(true);
        $refMethod->invoke($adapter, 'test_key', 'test_val');

        $refFetch = new \ReflectionMethod($adapter, 'doFetch');
        $refFetch->setAccessible(true);
        $this->assertSame('test_val', $refFetch->invoke($adapter, 'test_key'));

        $refContains = new \ReflectionMethod($adapter, 'doContains');
        $refContains->setAccessible(true);
        $this->assertTrue($refContains->invoke($adapter, 'test_key'));

        $refDelete = new \ReflectionMethod($adapter, 'doDelete');
        $refDelete->setAccessible(true);
        $refDelete->invoke($adapter, 'test_key');

        $refFlush = new \ReflectionMethod($adapter, 'doFlush');
        $refFlush->setAccessible(true);
        $refFlush->invoke($adapter);

        $refStats = new \ReflectionMethod($adapter, 'doGetStats');
        $refStats->setAccessible(true);
        $this->assertNull($refStats->invoke($adapter));
    }

    public function testControllerFactoryFilter(): void
    {
        CamooDi::create();
        $filter = new ControllerFactoryFilter(ErrorController::class);
        $this->assertInstanceOf(ErrorController::class, $filter->getInstance());
    }

    public function testDefaultModule(): void
    {
        $collection = new ModuleCollection();
        $module = new DefaultModule($collection);
        $this->assertInstanceOf(DefaultModule::class, $module);
    }

    public function testApplicationClass(): void
    {
        Configure::write('App.namespace', 'CAMOO\\Test\\TestCase\\Di');
        $app = new DiApplication();
        $app->modules(new ModuleCollection());
        $this->assertTrue(true);
    }
}
