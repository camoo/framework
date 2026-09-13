<?php

declare(strict_types=1);

namespace CAMOO\Test\TestCase\Template\Extension;

use CAMOO\Http\ServerRequest;
use CAMOO\Template\Extension\Functions\Form;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Form::class)]
class FormTest extends TestCase
{
    private Form $form;

    public function setUp(): void
    {
        $request = new ServerRequest();
        $this->form = new Form($request, null, 'test_token');
    }

    public function testGetFunctions(): void
    {
        $funcs = $this->form->getFunctions();
        $this->assertCount(3, $funcs);
    }

    public function testFormStartAndEnd(): void
    {
        $start = $this->form->formStart('my_form', ['url' => '/action']);
        $this->assertStringContainsString('name="my_form"', $start);
        $this->assertStringContainsString('action="/action"', $start);
        $this->assertStringContainsString('__csrf_Token', $start);

        $end = $this->form->formEnd();
        $this->assertSame('</form>', $end);
    }

    public function testFormStartAlwaysContainsCsrfToken(): void
    {
        $form = new Form(new ServerRequest());

        self::assertStringContainsString('name="__csrf_Token"', $form->formStart());
    }

    public function testInputTypes(): void
    {
        $text = $this->form->input('username', ['value' => 'john']);
        $this->assertStringContainsString('name="username"', $text);
        $this->assertStringContainsString('value="john"', $text);

        $textarea = $this->form->input('bio', ['type' => 'textarea', 'value' => 'Hello']);
        $this->assertStringContainsString('<textarea name="bio"', $textarea);
        $this->assertStringContainsString('>Hello</textarea>', $textarea);

        $submit = $this->form->input('btn', ['type' => 'submit', 'value' => 'Send']);
        $this->assertStringContainsString('<button ', $submit);
        $this->assertStringContainsString('>Send</button>', $submit);

        $email = $this->form->input('email', ['value' => 'a@b.com']);
        $this->assertStringContainsString('type="email"', $email);

        $pwd = $this->form->input('password');
        $this->assertStringContainsString('type="password"', $pwd);
    }

    public function testInputEscapesAttributesAndPreservesZeroValues(): void
    {
        $input = $this->form->input('label', [
            'value' => '" onfocus="alert(1)',
            'data-label' => 'a&b',
        ]);
        self::assertStringNotContainsString('onfocus="alert(1)', $input);
        self::assertStringContainsString('&quot; onfocus=&quot;alert(1)', $input);
        self::assertStringContainsString('value="0"', $this->form->input('count', [
            'type' => 'hidden',
            'value' => '0',
        ]));
    }

    public function testRejectsEventHandlerAttributes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->form->input('label', ['onfocus' => 'alert(1)']);
    }

    public function testRejectsUnsafeFormActionScheme(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->form->formStart('my_form', ['url' => 'javascript:alert(1)']);
    }
}
