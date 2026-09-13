<?php

namespace CAMOO\Test\TestCase\Exception;

use CAMOO\Exception\ConsoleException;
use CAMOO\Exception\Exception;
use CAMOO\Exception\Http\BadRequestException;
use CAMOO\Exception\Http\BaseHttpException;
use CAMOO\Exception\Http\ForbiddenException;
use CAMOO\Exception\Http\InternalServerErrorException;
use CAMOO\Exception\Http\MethodNotAllowedException;
use CAMOO\Exception\Http\NotFoundException;
use CAMOO\Exception\Http\UnauthorizedException;
use CAMOO\Exception\MailerException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Exception::class)]
#[CoversClass(ConsoleException::class)]
#[CoversClass(MailerException::class)]
#[CoversClass(BaseHttpException::class)]
#[CoversClass(BadRequestException::class)]
#[CoversClass(ForbiddenException::class)]
#[CoversClass(InternalServerErrorException::class)]
#[CoversClass(MethodNotAllowedException::class)]
#[CoversClass(NotFoundException::class)]
#[CoversClass(UnauthorizedException::class)]
class ExceptionTest extends TestCase
{
    public function testBaseException(): void
    {
        $ex = new Exception('Custom Error', 400, ['field' => 'name'], 'Title', 'User Msg', ['X-Header' => 'val']);
        $this->assertSame(400, $ex->getStatusCode());
        $this->assertSame(['field' => 'name'], $ex->getErrorData());
        $this->assertSame('Title', $ex->getUserTitle());
        $this->assertSame('User Msg', $ex->getUserMessage());
        $this->assertSame(['X-Header' => 'val'], $ex->getHttpHeaders());
        $this->assertSame('Custom Error', $ex->getErrorDescription());

        $ex->setStatusCode(404)
           ->setErrorData('data')
           ->setUserTitle('New Title')
           ->setUserMessage('New Msg')
           ->setHttpHeaders(['A' => 'B'])
           ->setErrorDescription('Desc');

        $this->assertSame(404, $ex->getStatusCode());
        $this->assertSame('data', $ex->getErrorData());
        $this->assertSame('New Title', $ex->getUserTitle());
        $this->assertSame('New Msg', $ex->getUserMessage());
        $this->assertSame(['A' => 'B'], $ex->getHttpHeaders());
        $this->assertSame('Desc', $ex->getErrorDescription());
    }

    public function testHttpExceptions(): void
    {
        $bad = new BadRequestException();
        $this->assertSame(400, $bad->getStatusCode());

        $forbidden = new ForbiddenException();
        $this->assertSame(403, $forbidden->getStatusCode());

        $internal = new InternalServerErrorException();
        $this->assertSame(500, $internal->getStatusCode());

        $notAllowed = new MethodNotAllowedException();
        $this->assertSame(405, $notAllowed->getStatusCode());

        $notFound = new NotFoundException();
        $this->assertSame(404, $notFound->getStatusCode());

        $unauth = new UnauthorizedException();
        $this->assertSame(401, $unauth->getStatusCode());

        $console = new ConsoleException('cli error');
        $this->assertSame('cli error', $console->getMessage());

        $mailer = new MailerException('mail error');
        $this->assertSame('mail error', $mailer->getMessage());
    }
}
