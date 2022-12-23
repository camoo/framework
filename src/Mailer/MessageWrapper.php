<?php

declare(strict_types=1);

namespace CAMOO\Mailer;

use CAMOO\Exception\MailerException;
use Nette\Mail\Message as BaseMessage;

/**
 * Class MessageWrapper
 *
 * @method static      setFrom(string $email, string $name = null)                                       Sets the sender of the message. Email or format "John Doe" <doe@example.com>
 * @method array|null  getFrom()                                                                         Returns the sender of the message
 * @method static      addReplyTo(string $email, string $name = null)                                    Adds the reply-to address. Email or format "John Doe" <doe@example.com>
 * @method static      setSubject(string $subject)                                                       Sets the subject of the message
 * @method string|null getSubject()                                                                      Returns the subject of the message
 * @method static      addTo(string $email, string $name = null)                                         Adds email recipient. Email or format "John Doe" <doe@example.com>
 * @method static      addCc(string $email, string $name = null)                                         Adds carbon copy email recipient. Email or format "John Doe" <doe@example.com>
 * @method static      addBcc(string $email, string $name = null)                                        Adds blind carbon copy email recipient. Email or format "John Doe" <doe@example.com>
 * @method static      setReturnPath(string $email)                                                      Sets the Return-Path header of the message
 * @method string|null getReturnPath()                                                                   Returns the Return-Path header
 * @method static      setPriority(int $priority)                                                        Sets email priority
 * @method int|null    getPriority()                                                                     Returns email priority
 * @method static      setHtmlBody(string $html, string $basePath = null)                                Sets Html Body
 * @method string      getHtmlBody()                                                                     Gets HTML body
 * @method MimePart    addEmbeddedFile(string $file, string $content = null, string $contentType = null) Adds embedded file
 * @method static      addInlinePart(MimePart $part)                                                     Adds inlined Mime Part
 * @method MimePart    addAttachment(string $file, string $content = null, string $contentType = null)   Adds Attachment
 * @method array       getAttachments()                                                                  Gets all email attachments
 * @method string      generateMessage()                                                                 Returns encoded message
 * @method static      build()                                                                           Builds email. Does not modify itself, but returns a new object
 * @method static      setHeader(string $name, $value, bool $append = false)                             Sets a header
 * @method mixed       getHeader(string $name)                                                           Returns a header
 * @method static      clearHeader(string $name)                                                         Removes a header
 * @method string|null getEncodedHeader(string $name)                                                    Returns an encoded header
 * @method array       getHeaders()                                                                      Returns Headers
 * @method static      setContentType(string $contentType, string $charset = null)                       Sets Content-Type header
 * @method static      setEncoding(string $encoding)                                                     Sets Content-Transfer-Encoding header
 * @method string      getEncoding()                                                                     Returns Content-Transfer-Encoding header
 * @method static      addPart(self $part = null)                                                        Adds or creates new multipart
 * @method static      setBody(string $body)                                                             Sets textual body
 * @method string      getBody()                                                                         Gets textual body
 * @method string      getEncodedMessage()                                                               Returns encoded message
 *
 * @author CamooSarl
 */
final class MessageWrapper extends BaseMessage
{
    public function __construct(array $headersConfig)
    {
        static::$defaultHeaders = $headersConfig;
        parent::__construct();
    }

    /**
     * Forward the method call to Message Methodes
     *
     * @throws MailerException When the function is not valid
     */
    public function __call(string $function, array $arguments)
    {
        if (!method_exists($this, $function)) {
            throw new MailerException("{$function} is not a valid Message methode");
        }

        return @call_user_func_array([$this, $function], $arguments);
    }
}
