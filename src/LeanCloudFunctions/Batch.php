<?php

namespace App\LeanCloudFunctions;

use App\Collector;
use LeanCloud\Client;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

class Batch implements ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(array $params)
    {
        if (!isset($params['sessionToken'])) {
            return 'No session token';
        }

        $collector = $this->container->get('collector');
        Client::getStorage()->set('LC_SessionToken', $params['sessionToken']);

        $collector->collect();

        return 'OK';
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices()
    {
        return [
            'collector' => Collector::class
        ];
    }
}