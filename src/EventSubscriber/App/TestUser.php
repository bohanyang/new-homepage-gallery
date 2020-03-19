<?php

namespace App\EventSubscriber\App;

use LeanCloud\LeanObject;
use LeanCloud\User;

class TestUser
{
    public function __invoke(array $params, ?User $user, array $meta)
    {
        $user = $user ?? new LeanObject();
        return [$params, $user->toJSON(), $meta];
    }
}