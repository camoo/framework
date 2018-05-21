<?php
namespace CAMOO\File;

use Exception;

class Json
{
    /**
     * decode json string
     */
    protected function decode($sJSON, $bAsHash = false)
    {
        try {
            if (($xData = json_decode($sJSON, $bAsHash)) === null
                && (json_last_error() !== JSON_ERROR_NONE) ) {
                    throw new Exception(json_last_error_msg());
            }
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $xData;
    }

    public function read($sFile)
    {
        if (! file_exists($sFile)) {
            die('ERROR: Database Connection');
        }
        $sData = file_get_contents($sFile);
        return $this->decode($sData, true);
    }
}
