<?php

namespace App\Repository;

interface RepositoryContract
{
    public function getImage(string $name);

    public function listImages(int $limit, int $page = 1);
}
