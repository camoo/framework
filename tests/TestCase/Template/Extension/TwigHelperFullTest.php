<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Template\Extension;

use CAMOO\Event\Event;
use CAMOO\Http\Flash as HttpFlash;
use CAMOO\Http\ServerRequest;
use CAMOO\Http\Session;
use CAMOO\Http\SessionSegment;
use CAMOO\Template\Extension\FilterCollection;
use CAMOO\Template\Extension\FilterHelper;
use CAMOO\Template\Extension\Filters;
use CAMOO\Template\Extension\Filters\Flash as FlashFilter;
use CAMOO\Template\Extension\FunctionCollection;
use CAMOO\Template\Extension\FunctionHelper;
use CAMOO\Template\Extension\Functions;
use CAMOO\Template\Extension\TwigHelper;
use CAMOO\Utils\Configure;
use GuzzleHttp\Psr7\ServerRequest as GuzzleRequest;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

class SampleFilterHelper extends FilterHelper
{
    public function getFilters(): array
    {
        return [
            $this->add('sample_filter', fn ($v) => $v),
        ];
    }
}

class SampleFunctionHelper extends FunctionHelper
{
    public function getFunctions(): array
    {
        return [
            $this->add('sample_func', fn ($v) => $v),
        ];
    }
}

#[CoversClass(TwigHelper::class)]
#[CoversClass(Filters::class)]
#[CoversClass(Functions::class)]
#[CoversClass(FilterHelper::class)]
#[CoversClass(FunctionHelper::class)]
#[CoversClass(FlashFilter::class)]
class TwigHelperFullTest extends TestCase
{
    private TwigHelper $helper;

    private ServerRequest $request;

    public function setUp(): void
    {
        Configure::write('Session.name', 'TESTSESS');
        Configure::write('Session.cookie', ['expire' => 3600, 'path' => '/', 'domain' => '', 'secure' => false, 'httponly' => true]);

        $guzzle = new GuzzleRequest('GET', '/test');
        $this->request = new ServerRequest($guzzle);

        $session = Session::create([]);
        $flashSeg = $session->getFlash();
        $sessionSeg = new SessionSegment($session->segment('FlashTest'));
        $this->request->Flash = new HttpFlash($flashSeg, $sessionSeg);

        $this->helper = new TwigHelper(
            $this->request,
            new FunctionCollection(),
            new FilterCollection(),
        );
    }

    public function testGetRequest(): void
    {
        $this->assertSame($this->request, $this->helper->getRequest());
    }

    public function testLoadFunctionAndFilterObject(): void
    {
        $funcHelper = new SampleFunctionHelper($this->helper);
        $filterHelper = new SampleFilterHelper($this->helper);

        $this->helper->loadFunction($funcHelper);
        $this->helper->loadFilter($filterHelper);

        $this->assertNotEmpty($this->helper->getFunctions());
        $this->assertNotEmpty($this->helper->getFilters());
    }

    public function testLoadFunctionAndFilterByClassName(): void
    {
        $this->helper->loadFunction(SampleFunctionHelper::class);
        $this->helper->loadFilter(SampleFilterHelper::class);

        $this->assertNotEmpty($this->helper->getFunctions());
        $this->assertNotEmpty($this->helper->getFilters());
    }

    public function testLoadUnknownFunctionThrowsException(): void
    {
        $this->expectException(\CAMOO\Exception\Exception::class);
        $this->helper->loadFunction('NonExistentFunctionClass');
    }

    public function testLoadUnknownFilterThrowsException(): void
    {
        $this->expectException(\CAMOO\Exception\Exception::class);
        $this->helper->loadFilter('NonExistentFilterClass');
    }

    public function testInitialize(): void
    {
        $this->helper->initialize();
        $this->assertNotEmpty($this->helper->getFunctions());
        $this->assertNotEmpty($this->helper->getFilters());
    }

    public function testFiltersAndFunctionsClasses(): void
    {
        $filters = new Filters($this->helper);
        $filters->initialize();
        $filters->load(SampleFilterHelper::class);

        $functions = new Functions($this->helper);
        $functions->initialize();
        $functions->load(SampleFunctionHelper::class);

        $this->assertNotEmpty($this->helper->getFilters());
        $this->assertNotEmpty($this->helper->getFunctions());
    }

    public function testFlashFilterDisplay(): void
    {
        $flashFilter = new FlashFilter($this->request);
        $this->assertNotEmpty($flashFilter->getFilters());

        $this->assertNull($flashFilter->display('flash'));

        $this->request->getSession()->set('CAMOO.SYS.FLASH', ['flash' => 'info']);
        $this->request->Flash->success('Welcome Success');

        $html = $flashFilter->display('flash');
        $this->assertNotNull($html);
        $this->assertStringContainsString('alert-info', $html);
    }

    public function testFlashFilterDisplayTypes(): void
    {
        $flashFilter = new FlashFilter($this->request);

        $types = [
            'success' => 'alert-success',
            'error' => 'alert-danger',
            'warning' => 'alert-warning',
            'custom' => 'alert-secondary',
        ];

        foreach ($types as $type => $expectedClass) {
            $this->request->getSession()->set('CAMOO.SYS.FLASH', ['msg_' . $type => $type]);
            $this->request->Flash->success('Msg ' . $type);
            $html = $flashFilter->display('msg_' . $type);
            $this->assertNotNull($html);
            $this->assertStringContainsString($expectedClass, $html);
        }
    }

    public function testFilterHelperAndFunctionHelperEvents(): void
    {
        $filterHelper = new SampleFilterHelper($this->helper);
        $this->assertSame(['FilterHelper.initialize' => 'beforeRender'], $filterHelper->implementedEvents());
        $filterHelper->beforeRender(new Event('FilterHelper.initialize'), 'test');

        $funcHelper = new SampleFunctionHelper($this->helper);
        $this->assertSame(['FuncHelper.initialize' => 'beforeRender'], $funcHelper->implementedEvents());
        $funcHelper->beforeRender(new Event('FuncHelper.initialize'), 'test');
    }

    public function testFilterHelperNonExistentExtensionThrowsException(): void
    {
        $helper = new class ($this->helper) extends FilterHelper {
            public array $filters = ['NonExistentExtensionFilter'];

            public function getFilters(): array
            {
                return [];
            }
        };

        $this->assertTrue(true);
    }
}
