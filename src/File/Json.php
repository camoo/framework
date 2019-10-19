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
        if (($xData = json_decode($sJSON, $bAsHash)) !== null
                && (json_last_error() === JSON_ERROR_NONE)) {
            return $xData;
        }
    }

    public function read($sFile)
    {
        if (! file_exists($sFile)) {
            trigger_error("$sFile does not exist!", E_USER_ERROR);
        }
        $sData = file_get_contents($sFile);
        return $this->decode($sData, true);
    }
}
