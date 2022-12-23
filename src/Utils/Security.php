<?php

declare(strict_types=1);

namespace CAMOO\Utils;

/**
 * Class Security
 *
 * @author CamooSarl
 */
class Security
{
    public static function satanizer(?string $str, bool $keep_newlines = false)
    {
        $filtered = (string)$str;
        if (!mb_check_encoding($filtered, 'UTF-8')) {
            return '';
        }
        if (strpos($filtered, '<') !== false) {
            $callback = function ($match) {
                if (false === strpos($match[0], '>')) {
                    return htmlentities($match[0], ENT_QUOTES | ENT_IGNORE, 'UTF-8');
                }

                return $match[0];
            };
            $filtered = preg_replace_callback('%<[^>]*?((?=<)|>|$)%', $callback, $filtered);
            $filtered = self::stripAllTags($filtered, false);
            $filtered = str_replace("<\n", "&lt;\n", $filtered);
        }
        if (!$keep_newlines) {
            $filtered = preg_replace('/[\r\n\t ]+/', ' ', $filtered);
        }
        $filtered = trim($filtered);
        $found = false;
        while (preg_match('/%[a-f0-9]{2}/i', $filtered, $match)) {
            $filtered = str_replace($match[0], '', $filtered);
            $found = true;
        }
        if ($found) {
            $filtered = trim(preg_replace('/ +/', ' ', $filtered));
        }

        return $filtered;
    }

    public static function stripAllTags(string $string, bool $remove_breaks = false): string
    {
        $string = preg_replace('@<(script|style)[^>]*?>.*?</\\1>@si', '', $string);
        $string = strip_tags($string);
        if ($remove_breaks) {
            $string = preg_replace('/[\r\n\t ]+/', ' ', $string);
        }

        return trim($string);
    }
}
