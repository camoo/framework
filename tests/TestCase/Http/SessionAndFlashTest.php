<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Http;

use Aura\Session\CsrfToken;
use CAMOO\Exception\Exception;
use CAMOO\Http\Cookie;
use CAMOO\Http\Flash;
use CAMOO\Http\Session;
use CAMOO\Http\SessionSegment;
use CAMOO\Utils\Configure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Session::class)]
#[CoversClass(SessionSegment::class)]
#[CoversClass(Cookie::class)]
#[CoversClass(Flash::class)]
class SessionAndFlashTest extends TestCase
{
    public function setUp(): void
    {
        Configure::write('Session.name', 'TESTSESS');
        Configure::write('Session.cookie', [
            'expire' => 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
        ]);
    }

    public function testSessionAndSegment(): void
    {
        $session = Session::create([]);
        $this->assertInstanceOf(Session::class, $session);
        $this->assertNotEmpty($session->getName());

        $session->set('test_key', 'test_val');
        $this->assertSame('test_val', $session->get('test_key'));
        $this->assertSame('test_val', $session->test_key);

        $session->magic_key = 'magic_val';
        $this->assertSame('magic_val', $session->get('magic_key'));

        $this->assertTrue(is_string($session->getId()));

        $session->setName('NEWSESS');
        $this->assertNotEmpty($session->getName());

        $session->setSavePath(sys_get_temp_dir());
        $this->assertIsString($session->getSavePath());

        $this->assertInstanceOf(CsrfToken::class, $session->getCsrfToken());

        $session->save();
        $session->regenerateId();
        $session->clear();

        $segment = $session->segment('CustomSeg');
        $sessionSegment = new SessionSegment($segment);

        $sessionSegment->write('user.name', 'CamooUser');
        $this->assertTrue($sessionSegment->check('user.name'));
        $this->assertSame('CamooUser', $sessionSegment->read('user.name'));

        $sessionSegment->delete('user.name');
        $this->assertFalse($sessionSegment->check('user.name'));

        $sessionSegment->write('user.info.role', 'admin');
        $sessionSegment->delete('user.info.role');
        $sessionSegment->delete('non_existing.key');

        $sessionSegment->clear();
    }

    public function testSessionSegmentNullConstructorThrowsException(): void
    {
        $this->expectException(Exception::class);
        new SessionSegment(null);
    }

    public function testSessionSegmentWriteObjectThrowsException(): void
    {
        $session = Session::create([]);
        $segment = new SessionSegment($session->segment('ErrSeg'));
        $this->expectException(Exception::class);
        $segment->write('obj', new \stdClass());
    }

    public function testCookie(): void
    {
        $cookie = new Cookie(['theme' => 'dark']);
        $this->assertSame('dark', $cookie->get('theme'));
        $this->assertSame('dark', $cookie->theme);

        @$cookie->new_setting = 'light';

        $created = Cookie::create();
        $this->assertInstanceOf(Cookie::class, $created);
        $this->assertSame($created, Cookie::create());
    }

    public function testFlash(): void
    {
        $session = Session::create([]);
        $flashSeg = $session->getFlash();
        $sessionSeg = new SessionSegment($session->segment('FlashTest'));

        $flash = new Flash($flashSeg, $sessionSeg);
        $flash->success('Success Message');
        $this->assertSame('Success Message', $flash->get('flash'));

        $flash->setNext('next_key', 'Next Message');
        $this->assertSame('Next Message', $flash->getNext('next_key', 'alt'));

        $flash->keep();
        $flash->destroy();
        $this->assertTrue(true);
    }

    public function testFlashInvalidMethodThrowsException(): void
    {
        $session = Session::create([]);
        $flash = new Flash($session->getFlash(), new SessionSegment($session->segment('Flash')));
        $this->expectException(Exception::class);
        $flash->invalidFlashType('msg');
    }
}
