<?php

namespace App;

use LeanCloud\User;

class TestRoute
{
    public function __invoke(array $params, ?User $user, array $meta)
    {
        return file_get_contents(__DIR__ . '/../config/routes.yaml');
    }
}