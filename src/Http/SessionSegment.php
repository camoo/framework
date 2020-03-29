<?php
declare(strict_types=1);

namespace CAMOO\Http;

use Aura\Session\Segment;
use \CAMOO\Exception\Exception;

/**
 * Class SessionSegment
 * @author CamooSarl
 */
final class SessionSegment
{

    /** @var Segment $segment */
    private $segment;

    public function __construct(?Segment $segment)
    {
        if (empty($segment)) {
            throw new Exception('Session Segment is missing!');
        }
        $this->segment = $segment;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function read(string $key)
    {
        return $this->segment->get($key);
    }

    /**
     * @param string $key
     * @param int|string|array|object|mixed $value
     */
    public function write(string $key, $value) : void
    {
        $this->segment->set($key, $value);
    }

    /**
     * @param string $key
     * @return bool
     */
    public function check(string $key) : bool
    {
        return !empty($this->segment->get($key));
    }
}
