<?php

namespace App\LeanCloudFunctions;

use App\Collector;
use LeanCloud\Client;

class Batch
{
    /** @var Collector */
    private $collector;

    public function __construct(Collector $collector)
    {
        $this->collector = $collector;
    }

    public function __invoke(array $params)
    {
        if (!isset($params['sessionToken'])) {
            return 'No session token';
        }

        Client::getStorage()->set('LC_SessionToken', $params['sessionToken']);

        $this->collector->collect();

        return 'OK';
    }
}
