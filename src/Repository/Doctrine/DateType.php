<?php

namespace App\Repository\Doctrine;

use App\Model\Date;
use DateTimeInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\DateImmutableType;
use Safe\Exceptions\DatetimeException;

class DateType extends DateImmutableType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    public const NAME = 'app_date';

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Date) {
            return $value->format($platform->getDateFormatString());
        }

        throw ConversionException::conversionFailedInvalidType($value, static::NAME, ['null', Date::class]);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return Date::createFromLocal($value);
        }

        $format = $platform->getDateFormatString();

        try {
            $date = Date::createFromFormat($format, $value);
        } catch (DatetimeException $e) {
            throw ConversionException::conversionFailedFormat($value, static::NAME, $format, $e);
        }

        return $date;
    }
}