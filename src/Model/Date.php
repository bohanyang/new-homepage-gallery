<?php

namespace App\Model;

use DateTimeInterface;
use DateTimeZone;
use Safe\DateTimeImmutable;

final class Date
{
    private $date;

    private function __construct()
    {
    }

    public static function createFromYmd(string $date)
    {
        $instance = new self();
        $instance->date = DateTimeImmutable::createFromFormat('!Ymd', $date, new DateTimeZone('UTC'));

        return $instance;
    }

    public static function createFromLocal(DateTimeInterface $date)
    {
        return self::createFromYmd($date->format('Ymd'));
    }

    public static function createFromUTC(DateTimeInterface $date)
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
