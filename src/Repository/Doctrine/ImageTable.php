<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

use function bin2hex;
use function hex2bin;

class ImageTable extends AbstractTable
{
    private const NAME = 'images';

    private const COLUMNS = [
        //'id' => 'integer',
        'id' => 'binary',
        'name' => 'string',
        'first_appeared' => 'date_immutable',
        'last_appeared' => 'date_immutable',
        'urlbase' => 'string',
        'copyright' => 'string',
        'wp' => 'boolean',
        'vid' => 'blob',
    ];

    private const INDEXES = [
        [self::PRIMARY_KEY_INDEX, ['id']]
    ];

    private const COLUMN_OPTIONS = [
        //'id' => ['autoincrement' => true, 'unsigned' => true],
        //'id' => ['length' => 12, 'fixed' => true, 'customSchemaOptions' => ['unique' => true]],
        'id' => ['length' => 12, 'fixed' => true],
        'name' => ['length' => 255, 'customSchemaOptions' => ['unique' => true]],
        'first_appeared' => ['notnull' => false],
        'last_appeared' => ['notnull' => false],
        'urlbase' => ['length' => 255],
        'copyright' => ['length' => 255],
        'vid' => ['notnull' => false, 'length' => 65535]
    ];

    private const QUERY_CALLBACKS = [
        'id' => 'hex2bin',
        'vid' => 'serialize',
    ];

    private const RESULT_CALLBACKS = [
        //'id' => 'convertToPHPValue',
        'id' => 'bin2hex',
        'vid' => 'deserialize',
        'wp' => 'convertToPHPValue'
    ];

    /** @var AbstractPlatform */
    private $platform;

    /** @var SerializerInterface */
    private $serializer;

    public function __construct(AbstractPlatform $platform, SerializerInterface $serializer)
    {
        parent::__construct(
            self::NAME,
            self::COLUMNS,
            self::INDEXES,
            self::COLUMN_OPTIONS,
            self::QUERY_CALLBACKS,
            self::RESULT_CALLBACKS
        );

        $this->platform = $platform;
        $this->serializer = $serializer;
    }

    public function convertToPHPValue($value, string $column)
    {
        $type = $this->getColumnType($column);

        return Type::getType($type)->convertToPHPValue($value, $this->platform);
    }

    public function serialize($data) : string
    {
        return $this->serializer->serialize($data);
    }

    public function deserialize(string $data)
    {
        return $this->serializer->deserialize($data);
    }

    public function bin2hex(string $bin) : string
    {
        return bin2hex($bin);
    }

    public function hex2bin(string $hex) : string
    {
        return hex2bin($hex);
    }
}
