<?php

namespace App\GeoIP;

use MaxMind\Db\Reader;

class MmdbChecker implements CheckerInterface
{
    /** @var Reader */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function isCN(string $ip): bool
    {
        $result = $this->reader->get($ip);

        return isset($result['country']['iso_code']) ? ($result['country']['iso_code'] === 'CN') : false;
    }
}
