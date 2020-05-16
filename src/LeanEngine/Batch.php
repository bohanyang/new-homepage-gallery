<?php

namespace App\LeanEngine;

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

        if (isset($params['market']) && isset($params['offset'])) {
            return $this->collector->collectOne($params['market'], $params['offset']);
        }

        $this->collector->collect();

        return 'OK';
    }
}
