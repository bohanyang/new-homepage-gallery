<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BlobType;

use function igbinary_serialize;
use function igbinary_unserialize;
use function is_resource;
use function Safe\fclose;
use function Safe\lzf_compress;
use function Safe\lzf_decompress;
use function Safe\stream_get_contents;

class SerializedBinaryType extends BlobType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    public const NAME = 'serialized_binary';

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (is_resource($value)) {
            $value = stream_get_contents($resource = $value, -1, 0);
            fclose($resource);
        }

        return igbinary_unserialize(lzf_decompress($value));
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        return lzf_compress(igbinary_serialize($value));
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
