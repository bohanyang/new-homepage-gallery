<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * This is not a general type for TINYINT but a base type for enums
 */
abstract class TinyIntType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        $vendor = $platform->getName();

        if ($vendor === 'mysql') {
            return 'TINYINT UNSIGNED';
        } elseif ($vendor === 'mssql') {
            return 'TINYINT';
        }

        return $platform->getSmallIntTypeDeclarationSQL(['unsigned' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType()
    {
        return ParameterType::INTEGER;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
