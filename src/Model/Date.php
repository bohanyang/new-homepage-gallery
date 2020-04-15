<?php

namespace App\Model;

use DateTimeInterface;
use DateTimeZone;
use Safe\DateTimeImmutable;
use Safe\Exceptions\DatetimeException;

final class Date
{
    /** @var DateTimeImmutable */
    private $date;

    private function __construct()
    {
    }

    /** @throws DatetimeException */
    public static function createFromFormat(string $format, string $date)
    {
        $instance = new self();
        $instance->date = DateTimeImmutable::createFromFormat('!' . $format, $date, new DateTimeZone('UTC'));

        return $instance;
    }

    public static function createFromYmd(string $date)
    {
        return self::createFromFormat('Ymd', $date);
    }

    public static function createFromLocal(DateTimeInterface $date)
    {
        return self::createFromYmd($date->format('Ymd'));
    }

    public static function createFromUTC(DateTimeInterface $date)
    {
        $instance = new self();
        $instance->date = (new DateTimeImmutable("@{$date->getTimestamp()}"))->setTime(0, 0);

        return $instance;
    }

    public function get() : DateTimeImmutable
    {
        return $this->date;
    }

    public function format(string $format) : string
    {
        return $this->date->format($format);
    }
}
