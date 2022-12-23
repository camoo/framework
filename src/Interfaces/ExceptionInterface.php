<?php

namespace CAMOO\Interfaces;

use Throwable;

/**
 * Class ExceptionInterface
 *
 * @author CamooSarl
 */
interface ExceptionInterface extends Throwable
{
    /**
     * The HTTP status code
     *
     * @return int
     */
    public function getStatusCode();

    /**
     * Return headers
     *
     * @return array
     */
    public function getHttpHeaders();

    /**
     * Data corresponding to the error
     *
     * @return array
     */
    public function getErrorData();

    /**
     * A user-friendly error description
     *
     * @return string
     */
    public function getUserMessage();
}
