<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Event\ConnectionEventArgs;

class OracleSessionInit extends \Doctrine\DBAL\Event\Listeners\OracleSessionInit
{
    public function postConnect(ConnectionEventArgs $args)
    {
        if ($args->getConnection()->getDatabasePlatform()->getName() === 'oracle') {
            parent::postConnect($args);
        }
    }
}
