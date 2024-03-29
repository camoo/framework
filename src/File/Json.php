<?php

declare(strict_types=1);

namespace CAMOO\File;

use CAMOO\Exception\Exception;
use stdClass;

class Json
{
    public function __construct(private ?string $file = null, private ?string $json = null)
    {
    }

    public function decode(?string $sJSON = null, bool $bAsHash = false): array|stdClass
    {
        $json = $sJSON ?? $this->json;

        if (null === $json) {
            throw new Exception('Cannot decode on NULL');
        }

        if (($xData = json_decode($json, $bAsHash)) !== null
                && (json_last_error() === JSON_ERROR_NONE)) {
            return $xData;
        }

        throw new Exception(json_last_error_msg());
    }

    /**
     * Reads a json file
     *
     * @throws Exception
     */
    public function read(?string $sFile = null): array
    {
        $file = $sFile ?? $this->file;

        if (!is_file($file)) {
            throw new Exception(sprintf('%s does not exist !', $file));
        }

        $sData = file_get_contents($file);

        if ($sData === false) {
            throw new Exception(sprintf('Content of file %s cannot be read', $file));
        }

        return $this->decode($sData, true);
    }
}
