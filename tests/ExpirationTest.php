<?php

namespace App\Tests;

use App\Controller\Expiration;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Safe\DateTimeImmutable;

use function dump;

class ExpirationTest extends TestCase
{
    public function testNextHour()
    {
        $exp = new Expiration();

        $assertions = [
            '2020-04-01 23:59:59' => '2020-04-02 00:03:01',
            '2020-04-02 00:03:00' => '2020-04-02 00:03:01',
            '2020-04-02 00:03:01' => '2020-04-02 01:03:01',
        ];

        foreach ($assertions as $now => $expected) {
            $actual = $exp->nextHour(new DateTimeImmutable($now))->format('Y-m-d H:i:s');
            $this->assertSame($expected, $actual);
        }
    }

    public function testTomorrow()
    {
        $exp = new Expiration();

        $assertions = [
            '2020-04-01 23:59:59' => ['+0000', '2020-04-02 00:03:01'],
            '2020-04-02 00:03:00' => ['+0000', '2020-04-02 00:03:01'],
            '2020-04-02 00:03:01' => ['+0000', '2020-04-03 00:03:01'],
            '2020-04-02 10:44:59' => ['+1315', '2020-04-02 11:03:01'],
            '2020-04-02 10:48:01' => ['+1315', '2020-04-02 11:03:01'],
            '2020-04-02 11:03:00' => ['+1315', '2020-04-02 11:03:01'],
            '2020-04-02 11:03:01' => ['+1315', '2020-04-03 11:03:01'],
            '2020-04-02 03:14:59' => ['-0315', '2020-04-02 04:03:01'],
            '2020-04-02 03:18:01' => ['-0315', '2020-04-02 04:03:01'],
            '2020-04-02 04:03:00' => ['-0315', '2020-04-02 04:03:01'],
            '2020-04-02 04:03:01' => ['-0315', '2020-04-03 04:03:01'],
        ];

        $UTC = new DateTimeZone('UTC');

        foreach ($assertions as $now => [$timezone, $expected]) {
            $actual = $exp->tomorrow((new DateTimeImmutable($now, $UTC))->setTimezone(new DateTimeZone($timezone)));
            $this->assertSame($expected, $actual->setTimezone($UTC)->format('Y-m-d H:i:s'));
        }
    }
}
