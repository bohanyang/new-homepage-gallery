<?php

namespace App\LeanEngine;

use Psr\Log\LoggerInterface;

use function mt_rand;

class TestLog
{
    /** @var LoggerInterface */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function __invoke()
    {
        $this->logger->warning('Just a test: ' . mt_rand(1000, 9999));

        return 'Sent';
    }
}
