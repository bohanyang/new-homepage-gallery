<?php

namespace App\Repository;

use App\Repository\RecordBuilder\ImagePointer;
use DateTimeImmutable;

interface RepositoryContract
{
    public function getImage(string $name);

    public function listImages(int $limit, int $page);

    public function getArchive(string $market, DateTimeImmutable $date);

    public function findArchivesByDate(DateTimeImmutable $date);

    public function findImage(string $name) : ?ImagePointer;
}
