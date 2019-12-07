<?php

namespace App\GeoIP;

interface CheckerInterface
{
    public function isCN(string $ip): bool;
}
