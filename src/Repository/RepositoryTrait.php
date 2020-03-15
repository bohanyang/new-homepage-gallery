<?php

namespace App\Repository;

use InvalidArgumentException;

trait RepositoryTrait
{
    private function getSkip(int $limit, int $page) : int
    {
        if ($limit < 1) {
            throw new InvalidArgumentException('The limit should be an integer greater than or equal to 1.');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('The page number should be an integer greater than or equal to 1.');
        }

        return $limit * ($page - 1);
    }
}
