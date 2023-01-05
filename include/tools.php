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
    function debug(mixed $var, mixed ...$moreVars): void
    {
        if (Configure::read('debug')) {
            return;
        }
        $backtrace = debug_backtrace();
        $caller = array_shift($backtrace);
        $file = str_replace(ROOT, '', $caller['file']);
        dump($file . ' (line ' . $caller['line'] . ')');
        dump('########## DEBUG STARTS ##########');
        dump($var);
        if (!empty($moreVars)) {
            foreach ($moreVars as $v) {
                dump($v);
            }
        }
        dump('########## DEBUG ENDS ##########');
    }
}
