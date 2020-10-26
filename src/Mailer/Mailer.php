<?php

/**
 * This file extends the Nette Framework (https://nette.org)
 */

declare(strict_types=1);

namespace CAMOO\Mailer;

use Nette\Mail\MimePart;
use Nette\Mail\SmtpMailer;
use Nette\Mail\SmtpException;
use CAMOO\Utils\Configure;
use CAMOO\Exception\MailerException;

/**
 * Class Message provides functionality to compose and send both text and MIME-compliant multipart email messages.
 *
 * @method Message setFrom(string $email, string $name = null) Sets the sender of the message. Email or format "John Doe" <doe@example.com>
 * @method null|array getFrom() Returns the sender of the message
 * @method Message addReplyTo(string $email, string $name = null) Adds the reply-to address. Email or format "John Doe" <doe@example.com>
 * @method Message setSubject(string $subject) Sets the subject of the message
 * @method null|string getSubject() Returns the subject of the message
 * @method Message addTo(string $email, string $name = null) Adds email recipient. Email or format "John Doe" <doe@example.com>
 * @method Message addCc(string $email, string $name = null) Adds carbon copy email recipient. Email or format "John Doe" <doe@example.com>
 * @method Message addBcc(string $email, string $name = null) Adds blind carbon copy email recipient. Email or format "John Doe" <doe@example.com>
 * @method Message setReturnPath(string $email) Sets the Return-Path header of the message
 * @method null|string getReturnPath() Returns the Return-Path header
 * @method Message setPriority(int $priority) Sets email priority
 * @method null|int getPriority() Returns email priority
 * @method Message setHtmlBody(string $html, string $basePath = null) Sets Html Body
 * @method string getHtmlBody() Gets HTML body
 * @method MimePart addEmbeddedFile(string $file, string $content = null, string $contentType = null) Adds embedded file
 * @method Message addInlinePart(MimePart $part) Adds inlined Mime Part
 * @method MimePart addAttachment(string $file, string $content = null, string $contentType = null) Adds Attachment
 * @method array getAttachments() Gets all email attachments
 * @method string generateMessage() Returns encoded message
 * @method Message build() Builds email. Does not modify itself, but returns a new object
 * @method Message setHeader(string $name, $value, bool $append = false) Sets a header
 * @method mixed getHeader(string $name) Returns a header
 * @method Message clearHeader(string $name) Removes a header
 * @method null|string getEncodedHeader(string $name) Returns an encoded header
 * @method array getHeaders() Returns Headers
 *
 * @author CamooSarl
 */
class Mailer
{
    /** @var MessageWrapper $mail */
    private $mail;

    /** @var string $transport */
    private $transport;

    /** @var array */
    protected $defaultHeaders = [
        'MIME-Version' => '1.0',
        'X-Mailer' => 'CAMOO Framework',
    ];

    /**
     * @param string $transport
     * @param array $headersConfig
     */
    public function __construct(string $transport = 'default', array $headersConfig = [])
    {
        if (Configure::check('SmtpTransport.' . $transport) === false) {
            throw new MailerException(sprintf('Smtp Transport %s can not be found', $transport));
        }

        $this->transport = $transport;

        $headersConfig = array_merge($this->defaultHeaders, $headersConfig);
        $this->mail = new MessageWrapper($headersConfig);
    }

    /**
     * Call an internal method or a Message method handled by the wrapper.
     *
     * Wrap the BaseMessage PHP functions to call as method of Message object.
     *
     * @param  string       $method
     * @param  array        $arguments
     *
     * @return mixed
     */
    public function __call($method, array $arguments)
    {
        return $this->mail->__call($method, $arguments);
    }

    public function send() : void
    {
        $smtpMailer = new SmtpMailer(Configure::read('SmtpTransport.' . $this->transport));
        try {
            $smtpMailer->send($this->mail);
        } catch (SmtpException $exception) {
            throw new MailerException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }
    }
}
