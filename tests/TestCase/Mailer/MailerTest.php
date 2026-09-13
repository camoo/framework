<?php

namespace CAMOO\Test\TestCase\Mailer;

use CAMOO\Exception\MailerException;
use CAMOO\Mailer\Mailer;
use CAMOO\Mailer\MessageWrapper;
use CAMOO\Utils\Configure;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Mailer::class)]
#[CoversClass(MessageWrapper::class)]
class MailerTest extends TestCase
{
    public function setUp(): void
    {
        Configure::write('SmtpTransport.default', [
            'host' => '127.0.0.1',
            'port' => 2525,
            'username' => 'test@example.com',
            'password' => 'secret',
            'timeout' => 1,
        ]);
    }

    public function testMailerConfigurationAndMethods(): void
    {
        $mailer = new Mailer('default');
        $mailer->setSubject('Test Email');
        $mailer->setFrom('sender@example.com');
        $mailer->addTo('recipient@example.com');
        $mailer->setDomain('example.com');
        $mailer->addHeaders(['X-Custom' => 'HeaderValue']);

        $this->assertSame('Test Email', $mailer->getSubject());
    }

    public function testInvalidDomainThrowsException(): void
    {
        $mailer = new Mailer('default');
        $this->expectException(MailerException::class);
        $mailer->setDomain('invalid_domain');
    }

    public function testUnknownTransportThrowsException(): void
    {
        $this->expectException(MailerException::class);
        new Mailer('non_existent_transport');
    }

    public function testInvalidWrapperMethodThrowsException(): void
    {
        $wrapper = new MessageWrapper([]);
        $this->expectException(MailerException::class);
        $wrapper->nonExistentMethod();
    }

    public function testSendThrowsMailerExceptionWhenSmtpFails(): void
    {
        $mailer = new Mailer('default');
        $mailer->setSubject('Test Email');
        $mailer->addTo('recipient@example.com');
        $mailer->setDomain('example.com');

        $this->expectException(MailerException::class);
        $mailer->send();
    }
}
