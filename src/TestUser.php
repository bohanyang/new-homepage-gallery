<?php

namespace App;

use LeanCloud\LeanObject;
use LeanCloud\User;

class TestUser
{
    public function __invoke(array $params, User $user = null, array $meta = [])
    {
        $user = $user ?? new LeanObject('_User');
        return [$params, $user->toJSON(), $meta];
    }
}