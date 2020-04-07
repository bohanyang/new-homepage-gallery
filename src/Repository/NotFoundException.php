<?php

namespace App\Repository;

use App\Model\Date;
use RuntimeException;

class NotFoundException extends RuntimeException
{
    public static function image(string $name)
    {
        return new self("Image '${name}' not found");
    }

    public static function images()
    {
        return new self("No image found");
    }

    public static function record(string $market, Date $date)
    {
        return new self("No record of '${market}' on '{$date->format('Y/n/j')}' found");
    }

    public static function date(Date $date)
    {
        return new self("No record found on '{$date->format('Y/n/j')}'");
    }
}
