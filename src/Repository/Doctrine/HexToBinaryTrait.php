<?php

namespace App\Repository\Doctrine;

use function bin2hex;
use function hex2bin;

trait HexToBinaryTrait
{
    protected function bin2hex(string $bin) : string
    {
        return bin2hex($bin);
    }

    protected function hex2bin(string $hex) : string
    {
        return hex2bin($hex);
    }
}
