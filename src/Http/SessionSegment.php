<?php

declare(strict_types=1);

namespace CAMOO\Http;

use Aura\Session\Segment;
use CAMOO\Exception\Exception;
use CAMOO\Utils\QueryData;

/**
 * Class SessionSegment
 *
 * @author CamooSarl
 */
final class SessionSegment
{
    public function __construct(private ?Segment $segment)
    {
        if (empty($segment)) {
            throw new Exception('Session Segment is missing!');
        }
    }

    public function read(string $key): mixed
    {
        $hash = explode('.', $key);
        $key = array_shift($hash);
        $xValue = $this->segment->get($key);
        if (empty($hash)) {
            return $xValue;
        }
        $valueArray = is_array($xValue) ? $xValue : (array)$xValue;

        $dataFiltered = array_filter($valueArray, fn (mixed $val) => null !== $val);

        return  (new QueryData($dataFiltered))->get(implode('.', $hash));
    }

    public function write(string $key, mixed $value): void
    {
        if (is_object($value)) {
            throw new Exception(sprintf(
                'Invalid Type for %s ! The following Types are allowed %s',
                '$value',
                '<int|string|array|null>'
            ));
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
            $valueArray = is_array($xValue) ? $xValue : (array)$xValue;
            $dataFiltered = array_filter($valueArray, fn (mixed $val) => null !== $val);
        }
        $data = (new QueryData($dataFiltered));
        $data->set(implode('.', $hash), $value);
        $this->segment->set($key, $data->all());
    }

    public function check(string $key): bool
    {
        return null !== $this->read($key);
    }

    public function delete(string $key): void
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

        $valueArray = is_array($xValue) ? $xValue : (array)$xValue;
        $dataFiltered = array_filter($valueArray, fn (mixed $val) => null !== $val);

        if (empty($dataFiltered)) {
            return;
        }
        $data = (new QueryData($dataFiltered));
        $data->remove(implode('.', $hash));
        $this->segment->set($key, $data->all());
    }

    public function clear(): void
    {
        $this->segment->clear();
    }
}
