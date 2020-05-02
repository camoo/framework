<?php
use CAMOO\Utils\Configure;
use CAMOO\Exception\Exception;

if (!function_exists('whoops_add_stack_frame')) {
    function whoops_add_stack_frame($callback)
    {
        $callback();
    }
}

if (!function_exists('throw_http_exception')) {
    function throw_exception($handler)
    {
        whoops_add_stack_frame(function () {
            throw new Exception('Something broke!');
        });
    }
}

if (!function_exists('debug')) {
    function debug($var)
    {
        if (!Configure::read('debug')) {
            return $var;
        }

        return dump($var);
    }
}
