<?php

namespace App\Repository\Doctrine;

use App\Model\Date;
use DateTimeImmutable;

trait NormalizeDateTrait
{
    abstract protected function convertToPHPValue($value, string $type);

    protected function normalizeDate(string $date, string $type) : Date
    {
        /** @var DateTimeImmutable $date */
        $date = $this->convertToPHPValue($date, $type);

        return Date::createFromLocal($date);
    }

    protected function getNormalizedDate(Date $date) : DateTimeImmutable
    {
        return $date->get();
    }
}
