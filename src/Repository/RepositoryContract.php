<?php

namespace App\Repository;

use DateTimeInterface;

interface RepositoryContract
{
    public function getImage(string $name);

    public function listImages(int $limit, int $page = 1);

    public function getArchive(string $market, DateTimeInterface $date);
}
