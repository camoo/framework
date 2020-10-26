<?php
declare(strict_types=1);

namespace CAMOO\Mailer;

use Nette\Mail\Message as BaseMessage;

/**
 * Class MessageWrapper
 *
 * @author CamooSarl
 */
final class MessageWrapper extends BaseMessage
{

    /**
     * @param array $headersConfig
     */
    public function __construct(array $headersConfig)
    {
        static::$defaultHeaders = $headersConfig;
        parent::__construct();
    }
}
