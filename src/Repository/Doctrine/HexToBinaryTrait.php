<?php

namespace App\Repository\Doctrine;

use function bin2hex;
use function hex2bin;
use function is_resource;
use function Safe\fclose;
use function Safe\rewind;
use function Safe\stream_get_contents;

trait HexToBinaryTrait
{
    /**
     * @param string|resource $bin
     * @return string
     */
    protected function bin2hex($bin) : string
    {
        if (is_resource($bin)) {
            $resource = $bin;
            rewind($resource);
            $bin = stream_get_contents($resource);
            fclose($resource);
        }

        return bin2hex($bin);
    }

    protected function hex2bin(string $hex) : string
    {
        return hex2bin($hex);
    }
}
