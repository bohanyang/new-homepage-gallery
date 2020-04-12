<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

use function Safe\sprintf;

class MarketType extends TinyIntType
{
    private const NAME_MAPPINGS = [
        1 => 'zh-CN',
        2 => 'en-US',
        3 => 'ja-JP',
        4 => 'en-GB',
        5 => 'de-DE',
        6 => 'fr-FR',
        7 => 'en-AU',
        8 => 'en-CA',
        9 => 'fr-CA',
        10 => 'pt-BR',
        11 => 'en-IN',
    ];

    private const ID_MAPPINGS = [
        'zh-CN' => 1,
        'en-US' => 2,
        'ja-JP' => 3,
        'en-GB' => 4,
        'de-DE' => 5,
        'fr-FR' => 6,
        'en-AU' => 7,
        'en-CA' => 8,
        'fr-CA' => 9,
        'pt-BR' => 10,
        'en-IN' => 11,
    ];

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!isset(self::NAME_MAPPINGS[$value])) {
            throw ConversionException::conversionFailed($value, static::NAME);
        }

        return self::NAME_MAPPINGS[$value];
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (!isset(self::ID_MAPPINGS[$value])) {
            throw new ConversionException(
                sprintf(
                    "Could not convert PHP value '%s' of Doctrine Type %s to database value",
                    $value,
                    static::NAME
                )
            );
        }

        return self::ID_MAPPINGS[$value];
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return static::NAME;
    }

    public const NAME = 'market';
}
