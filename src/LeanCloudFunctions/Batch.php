<?php

namespace App\LeanCloudFunctions;

use App\Collector;
use LeanCloud\Client;
use Psr\Log\LoggerInterface;

class Batch
{
    /** @var Collector */
    private $collector;

    /** @var LoggerInterface */
    private  $logger;

    public function __construct(Collector $collector, LoggerInterface $logger)
    {
        $this->collector = $collector;
        $this->logger = $logger;
    }

    public function __invoke(array $params)
    {
        if (!isset($params['sessionToken'])) {
            return 'No session token';
        }

        Client::getStorage()->set('LC_SessionToken', $params['sessionToken']);

        $this->logger->notice('Start collect');

        $this->collector->collect();

        return 'OK';
    }
}