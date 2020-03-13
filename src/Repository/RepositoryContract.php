<?php

namespace App\Repository;

use DateTimeImmutable;

interface RepositoryContract
{
    public function getImage(string $name);

    public function listImages(int $limit, int $page);

    public function getArchive(string $market, DateTimeImmutable $date);

    public function findArchivesByDate(DateTimeImmutable $date);
}
