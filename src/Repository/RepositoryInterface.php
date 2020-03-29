<?php

namespace App\Repository;

use App\Repository\RecordBuilder\ImagePointer;
use DateTimeImmutable;

interface RepositoryInterface
{
    public function getRecordsByImageName(string $name);

    public function listImages(int $limit, int $skip = 0);

    public function getRecord(string $market, DateTimeImmutable $date);

    public function getRecordsByDate(DateTimeImmutable $date);
}
