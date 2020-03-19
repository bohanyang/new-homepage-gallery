<?php

namespace App;

use LeanCloud\User;

class TestClearCache
{
    public function __invoke(array $params, ?User $user, array $meta)
    {
        return exec(sprintf('rm -rf %s', __DIR__ . '/../var'));
    }
}
