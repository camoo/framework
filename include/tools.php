<?php

use CAMOO\Exception\Exception;
use CAMOO\Utils\Configure;

if (!function_exists('whoops_add_stack_frame')) {
    function whoops_add_stack_frame($callback)
    {
        $callback();
    }
}

if (!function_exists('throw_http_exception')) {
    function throw_exception($handler): void
    {
        whoops_add_stack_frame(function () {
            throw new Exception('Something broke!');
        });
    }
}

if (!function_exists('debug')) {
    function debug(mixed $var): mixed
    {
        if (!Configure::read('debug')) {
            return $var;
        }

        return dump($var);
    }
}
