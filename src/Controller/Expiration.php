<?php

namespace App\Controller;

use Safe\DateTimeImmutable;

class Expiration
{
    public function nextHour(DateTimeImmutable $now) : DateTimeImmutable
    {
        $delay = $now->setTime((int) $now->format('G'), 3, 1);

        if ($now < $delay) {
            return $delay;
        }

        return $delay->modify('+1 hour');
    }

    public function tomorrow(DateTimeImmutable $now) : DateTimeImmutable
    {
        $delay = $this->fixDelay($now->setTime(0, 3, 1));

        if ($now < $delay) {
            return $delay;
        }

        return $delay->modify('+1 day');
    }

    public function fixDelay(DateTimeImmutable $delay) : DateTimeImmutable
    {
        $offset = (int) $delay->format('Z');

        if ($offset === 0) {
            return $delay;
        }

        $offset = $offset % 3600;

        if ($offset < 0) {
            $offset += 3600;
        }

        return $delay->modify("+${offset} seconds");
    }
}
