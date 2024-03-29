<?php

declare(strict_types=1);

namespace CAMOO\Utils;

use Whoops\Util\Misc;

/**
 * Class Utility
 *
 * @author CamooSarl
 */
final class Utility
{
    public static function isGsm0338(string $utf8_string): bool
    {
        $gsm0338 = [
            '@', 'Δ', ' ', '0', '¡', 'P', '¿', 'p',
            '£', '_', '!', '1', 'A', 'Q', 'a', 'q',
            '$', 'Φ', '"', '2', 'B', 'R', 'b', 'r',
            '¥', 'Γ', '#', '3', 'C', 'S', 'c', 's',
            'è', 'Λ', '¤', '4', 'D', 'T', 'd', 't',
            'é', 'Ω', '%', '5', 'E', 'U', 'e', 'u',
            'ù', 'Π', '&', '6', 'F', 'V', 'f', 'v',
            'ì', 'Ψ', '\'', '7', 'G', 'W', 'g', 'w',
            'ò', 'Σ', '(', '8', 'H', 'X', 'h', 'x',
            'Ç', 'Θ', ')', '9', 'I', 'Y', 'i', 'y',
            "\n", 'Ξ', '*', ':', 'J', 'Z', 'j', 'z',
            'Ø', "\x1B", '+', ';', 'K', 'Ä', 'k', 'ä',
            'ø', 'Æ', ',', '<', 'L', 'Ö', 'l', 'ö',
            "\r", 'æ', '-', '=', 'M', 'Ñ', 'm', 'ñ',
            'Å', 'ß', '.', '>', 'N', 'Ü', 'n', 'ü',
            'å', 'É', '/', '?', 'O', '§', 'o', 'à',
        ];
        $len = mb_strlen($utf8_string, 'UTF-8');

        for ($i = 0; $i < $len; $i++) {
            if (!in_array(mb_substr($utf8_string, $i, 1, 'UTF-8'), $gsm0338)) {
                return false;
            }
        }

        return true;
    }

    public static function isCli(): bool
    {
        return Misc::isCommandLine();
    }
}
