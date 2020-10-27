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
 * @method static setFrom(string $email, string $name = null) Sets the sender of the message. Email or format "John Doe" <doe@example.com>
 * @method null|array getFrom() Returns the sender of the message
 * @method static addReplyTo(string $email, string $name = null) Adds the reply-to address. Email or format "John Doe" <doe@example.com>
 * @method static setSubject(string $subject) Sets the subject of the message
 * @method null|string getSubject() Returns the subject of the message
 * @method static addTo(string $email, string $name = null) Adds email recipient. Email or format "John Doe" <doe@example.com>
 * @method static addCc(string $email, string $name = null) Adds carbon copy email recipient. Email or format "John Doe" <doe@example.com>
 * @method static addBcc(string $email, string $name = null) Adds blind carbon copy email recipient. Email or format "John Doe" <doe@example.com>
 * @method static setReturnPath(string $email) Sets the Return-Path header of the message
 * @method null|string getReturnPath() Returns the Return-Path header
 * @method static setPriority(int $priority) Sets email priority
 * @method null|int getPriority() Returns email priority
 * @method static setHtmlBody(string $html, string $basePath = null) Sets Html Body
 * @method string getHtmlBody() Gets HTML body
 * @method MimePart addEmbeddedFile(string $file, string $content = null, string $contentType = null) Adds embedded file
 * @method static addInlinePart(MimePart $part) Adds inlined Mime Part
 * @method MimePart addAttachment(string $file, string $content = null, string $contentType = null) Adds Attachment
 * @method array getAttachments() Gets all email attachments
 * @method string generateMessage() Returns encoded message
 * @method static build() Builds email. Does not modify itself, but returns a new object
 * @method static setHeader(string $name, $value, bool $append = false) Sets a header
 * @method mixed getHeader(string $name) Returns a header
 * @method static clearHeader(string $name) Removes a header
 * @method null|string getEncodedHeader(string $name) Returns an encoded header
 * @method array getHeaders() Returns Headers
 * @method static setContentType(string $contentType, string $charset = null) Sets Content-Type header
 * @method static setEncoding(string $encoding) Sets Content-Transfer-Encoding header
 * @method string getEncoding() Returns Content-Transfer-Encoding header
 * @method static addPart(self $part = null) Adds or creates new multipart
 * @method static setBody(string $body) Sets textual body
 * @method string getBody() Gets textual body
 * @method string getEncodedMessage() Returns encoded message
 *
 * @author CamooSarl
 */
class Mailer
{
    /** @var MessageWrapper $mail */
    private $mail;

    /** @var string $transport */
    private $transport;

    /** @var null|string $domain */
    private $domain;

    /** @var array */
    protected $defaultHeaders = [
        'MIME-Version' => '1.0',
        'X-Mailer' => 'CAMOO Framework',
    ];

    /**
     * @param string $transport
     * @param array $headersConfig
     */
    public function __construct(string $transport = 'default')
    {
        if (Configure::check('SmtpTransport.' . $transport) === false) {
            throw new MailerException(sprintf('Smtp Transport %s can not be found', $transport));
        }

        $this->transport = $transport;

        $this->mail = new MessageWrapper($this->defaultHeaders);
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
    public function __call(string $method, array $arguments)
    {
        return $this->mail->__call($method, $arguments);
    }

    /**
     * Sets client Host. Important when emails are sending with background process
     *
     * @param string $domain
     *
     * @throw MailerException
     *
     * @return self
     */
    public function setDomain(string $domain) : self
    {
        if (!preg_match('/^(?:[a-zA-Z0-9]+(?:\-*[a-zA-Z0-9])*\.)+[a-zA-Z]{2,}$/', $domain)) {
            throw new MailerException(sprintf('%s is not a valid domain', $domain));
        }

        $this->domain = $domain;
        return $this;
    }

    /**
     * Sets multiple headers
     *
     * @param array $headers
     *
     * @return void
     */
    public function addHeaders(array $headers) : void
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
    }

    /**
     * Sends the message out via SMTP
     *
     * @throw MailerException
     *
     * @return void
     */
    public function send() : void
    {
        $mailOption = Configure::read('SmtpTransport.' . $this->transport);
        if ($this->domain !== null) {
            $mailOption['clientHost'] = $this->domain;
        }
        $smtpMailer = new SmtpMailer($mailOption);
        try {
            $from = $this->mail->getFrom();
            if ($from === null) {
                $from = Configure::read('SmtpTransport.' . $this->transport. '.username');
                $this->mail->setFrom($from);
            }
            $smtpMailer->send($this->mail);
        } catch (SmtpException $exception) {
            throw new MailerException($exception->getMessage(), $exception->getCode(), $exception->getPrevious());
        }
    }
}
