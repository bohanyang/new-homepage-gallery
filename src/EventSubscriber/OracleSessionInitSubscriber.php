<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Event\Listeners\OracleSessionInit;

class OracleSessionInitSubscriber extends OracleSessionInit
{
    public function postConnect(ConnectionEventArgs $args)
    {
        if ($args->getConnection()->getDatabasePlatform()->getName() === 'oracle') {
            parent::postConnect($args);
        }
    }
}
