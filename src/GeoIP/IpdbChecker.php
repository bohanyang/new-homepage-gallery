<?php

namespace App\GeoIP;

use ipip\db\Reader;

class IpdbChecker implements CheckerInterface
{
    /** @var Reader */
    private $reader;

    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

    public function isCN(string $ip): bool
    {
        $result = $this->reader->findMap($ip, 'CN');

        return isset($result['country_name']) ? (
            ($result['country_name'] === '中国') &&
            ($result['region_name'] !== '台湾') &&
            ($result['region_name'] !== '香港') &&
            ($result['region_name'] !== '澳门')
        ) : false;
    }
}
