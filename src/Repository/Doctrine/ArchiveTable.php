<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;

use function bin2hex;
use function hex2bin;

class ArchiveTable extends AbstractTable
{
    private const NAME = 'archives';

    private const COLUMNS = [
        //'id' => 'integer',
        'id' => 'binary',
        'market' => 'smallint',
        'date_' => 'date_immutable',
        //'image_id' => 'integer',
        'image_id' => 'binary',
        'description' => 'text',
        'link' => 'string',
        'hotspots' => 'blob',
        'messages' => 'blob',
        'coverstory' => 'blob',
    ];

    private const INDEXES = [
        [self::PRIMARY_KEY_INDEX, ['id']],
        [self::NORMAL_INDEX, ['date_']],
        [self::NORMAL_INDEX, ['image_id']],
        //[self::NORMAL_INDEX, ['image_object_id']]
    ];

    private const COLUMN_OPTIONS = [
        //'id' => ['autoincrement' => true, 'unsigned' => true],
        'id' => ['length' => 12, 'fixed' => true],
        'market' => ['unsigned' => true],
        'description' => ['length' => 65535],
        //'image_id' => ['unsigned' => true],
        'image_id' => ['length' => 12, 'fixed' => true],
        'link' => ['notnull' => false, 'length' => 2083],
        'hotspots' => ['notnull' => false, 'length' => 65535],
        'messages' => ['notnull' => false, 'length' => 65535],
        'coverstory' => ['notnull' => false, 'length' => 65535]
    ];

    private const QUERY_CALLBACKS = [
        'id' => 'hex2bin',
        'market' => 'convertToMarketId',
        'image_id' => 'hex2bin',
        'hotspots' => 'serialize',
        'messages' => 'serialize',
        'coverstory' => 'serialize'
    ];

    private const RESULT_CALLBACKS = [
        //'id' => 'convertToPHPValue',
        'id' => 'bin2hex',
        'date_' => 'convertToPHPValue',
        //'appeared_on' => 'convertToPHPValue',
        'market' => 'convertToMarketName',
        //'image_id' => 'convertToPHPValue',
        'image_id' => 'bin2hex',
        'hotspots' => 'deserialize',
        'messages' => 'deserialize',
        'coverstory' => 'deserialize'
    ];

    private const FIELD_MAPPINGS = [
        'date_' => 'date'
    ];

    private const COLUMN_NAME_MAPPINGS = [
        'date' => 'date_'
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
            self::RESULT_CALLBACKS,
            self::FIELD_MAPPINGS,
            self::COLUMN_NAME_MAPPINGS
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

    private const MARKET_NAME_MAPPINGS = [
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

    private const MARKET_ID_MAPPINGS = [
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

    public static function generateMarketIdMappings() : array
    {
        return array_flip(self::MARKET_NAME_MAPPINGS);
    }

    public function convertToMarketName(string $id) : string
    {
        $id = $this->convertToPHPValue($id, 'market');

        if (isset(self::MARKET_NAME_MAPPINGS[$id])) {
            return self::MARKET_NAME_MAPPINGS[$id];
        }

        throw new InvalidArgumentException("Cannot convert market id ${id} to name");
    }

    public function convertToMarketId(string $name) : int
    {
        if (isset(self::MARKET_ID_MAPPINGS[$name])) {
            return self::MARKET_ID_MAPPINGS[$name];
        }

        throw new InvalidArgumentException("Cannot convert market name ${name} to id");
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
