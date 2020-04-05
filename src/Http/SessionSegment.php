<?php
declare(strict_types=1);

namespace CAMOO\Http;

use Aura\Session\Segment;
use \CAMOO\Exception\Exception;
use \CAMOO\Utils\QueryData;

/**
 * Class SessionSegment
 * @author CamooSarl
 */
final class SessionSegment
{

    /** @var \Aura\Session\Segment $segment */
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
     * @return int|string|array|object|mixed $value
     */
    public function read(string $key)
    {
        $hash = explode('.', $key);
        $key = array_shift($hash);
        $xValue = $this->segment->get($key);
        if (empty($hash)) {
            return $xValue;
        }
        $valueArray = is_array($xValue)? $xValue :  (array) $xValue;
        $dataFiltered = array_filter($valueArray, function ($val) {
            return null !== $val;
        });
        return  (new QueryData($dataFiltered))->get(implode('.', $hash));
    }

    /**
     * @param string $key
     * @param int|string|array|null $value
     */
    public function write(string $key, $value) : void
    {
        if (is_object($value)) {
            throw new Exception(sprintf('Invalid Type for %s ! The following Types are allowed %s', '$value', '<int|string|array|null>'));
        }

        $hash = explode('.', $key);
        $key = array_shift($hash);

        if (empty($hash)) {
            $this->segment->set($key, $value);
            return;
        }

        $xValue = $this->segment->get($key);
        $dataFiltered = [];
        if (null !== $xValue) {
            $valueArray = is_array($xValue)? $xValue :  (array) $xValue;
            $dataFiltered = array_filter($valueArray, function ($val) {
                return null !== $val;
            });
        }

        $data = (new QueryData($dataFiltered));
        $data->set(implode('.', $hash), $value);
        $this->segment->set($key, $data->all());
    }

    /**
     * @param string $key
     * @return bool
     */
    public function check(string $key) : bool
    {
        return null !== $this->read($key);
    }

    /**
     * @param string $key
     * @return void
     */
    public function delete(string $key) : void
    {
        $hash = explode('.', $key);
        $key = array_shift($hash);
        $xValue = $this->segment->get($key);

        if (null === $xValue) {
            return;
        }

        if (empty($hash)) {
            $this->segment->set($key, null);
            return;
        }

        $valueArray = is_array($xValue)? $xValue :  (array) $xValue;
        $dataFiltered = array_filter($valueArray, function ($val) {
            return null !== $val;
        });
        if (empty($dataFiltered)) {
            return;
        }
        $data = (new QueryData($dataFiltered));
        $data->remove(implode('.', $hash));
        $this->segment->set($key, $data->all());
    }

    /**
     * @return void
     */
    public function clear() : void
    {
        $this->segment->clear();
    }

}
