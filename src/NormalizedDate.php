<?php

namespace App;

use DateTimeInterface;
use Safe\DateTimeImmutable;

class NormalizedDate
{
    private $date;

    private function __construct()
    {
    }

    public static function fromDate(DateTimeInterface $date)
    {
        $instance = new self();
        $instance->date = DateTimeImmutable::createFromFormat('Y-m-d', "!{$date->format('Y-m-d')}");

        return $instance;
    }

    public static function fromTimestamp(DateTimeInterface $date)
    {
        $instance = new self();
        $date = new DateTimeImmutable("@{$date->getTimestamp()}");
        $instance->date = $date->setTime(0, 0, 0);

        return $instance;
    }

    public function get() : DateTimeImmutable
    {
        return $this->date;
    }
}
