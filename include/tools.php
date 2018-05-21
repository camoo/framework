<?php
use CAMOO\Utils\Configure;

if (!function_exists('whoops_add_stack_frame')) {
    function whoops_add_stack_frame($callback)
    {
        $callback();
    }
}

if (!function_exists('throw_http_exception')) {
    function throw_exception()
    {
        whoops_add_stack_frame(function () {
            throw \CAMOO\Exception\Exception('Something broke!');
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
