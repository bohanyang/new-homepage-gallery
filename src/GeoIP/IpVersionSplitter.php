<?php

namespace App\GeoIP;

use InvalidArgumentException;

class IpVersionSplitter implements CheckerInterface
{
    /** @var CheckerInterface */
    private $v4checker;

    /** @var CheckerInterface */
    private $v6checker;

    public function __construct(CheckerInterface $v4checker, CheckerInterface $v6checker)
    {
        $this->v4checker = $v4checker;
        $this->v6checker = $v6checker;
    }

    public function isCN(string $ip): bool
    {
        return $this->isIpv6($ip) ? $this->v6checker->isCN($ip) : $this->v4checker->isCN($ip);
    }

    private function isIpv6(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        throw new InvalidArgumentException('IP is invalid.');
    }
}
