<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class ImageTable extends AbstractTable
{
    private const NAME = 'images_t0g3qd54a';

    private const COLUMNS = [
        'id' => 'integer',
        //'object_id' => 'binary',
        'name' => 'string',
        //'appeared_on' => 'date_immutable',
        'urlbase' => 'string',
        'copyright' => 'string',
        'wp' => 'boolean',
        'vid' => 'blob',
    ];

    private const INDEXES = [
        [self::PRIMARY_KEY_INDEX, ['id']]
    ];

    private const COLUMN_OPTIONS = [
        'id' => ['autoincrement' => true, 'unsigned' => true],
        //'object_id' => ['length' => 12, 'fixed' => true, 'customSchemaOptions' => ['unique' => true]],
        'name' => ['length' => 255, 'customSchemaOptions' => ['unique' => true]],
        //'appeared_on' => ['notnull' => false],
        'urlbase' => ['length' => 255],
        'copyright' => ['length' => 255],
        'vid' => ['notnull' => false, 'length' => 65535]
    ];

    private const QUERY_CALLBACKS = [
        'vid' => 'serialize',
    ];

    private const RESULT_CALLBACKS = [
        'id' => 'convertToPHPValue',
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

    public function deserialize(string $data) : string
    {
        return $this->serializer->deserialize($data);
    }
}
