<?php

namespace App\Repository\Doctrine;

use App\Model\Date;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

class RecordTable extends AbstractTable
{
    use HexToBinaryTrait;
    use NormalizeDateTrait;
    use SerializeTrait;

    protected const NAME = 'records';

    protected const COLUMNS = [
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

    protected $indexes = [
        [self::PRIMARY_KEY_INDEX, ['id']],
        [self::NORMAL_INDEX, ['date_']],
        [self::NORMAL_INDEX, ['image_id']],
        [self::NORMAL_INDEX, ['market', 'date_']]
        //[self::NORMAL_INDEX, ['image_object_id']]
    ];

    protected $columnOptions = [
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

    protected $queryCallbacks = [
        'id' => 'hex2bin',
        'market' => 'convertToMarketId',
        'date_' => 'getNormalizedDate',
        'image_id' => 'hex2bin',
        'hotspots' => 'serialize',
        'messages' => 'serialize',
        'coverstory' => 'serialize'
    ];

    protected $resultCallbacks = [
        //'id' => 'convertToPHPValue',
        'id' => 'bin2hex',
        //'date_' => 'convertToPHPValue',
        'date_' => 'normalizeDate',
        //'appeared_on' => 'convertToPHPValue',
        'market' => 'convertToMarketName',
        //'image_id' => 'convertToPHPValue',
        'image_id' => 'bin2hex',
        'hotspots' => 'deserialize',
        'messages' => 'deserialize',
        'coverstory' => 'deserialize'
    ];

    protected $fieldMappings = [
        'date_' => 'date'
    ];

    protected $columnNameMappings = [
        'date' => 'date_'
    ];

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

    protected function convertToMarketName(string $id, string $type) : string
    {
        $id = $this->convertToPHPValue($id, $type);

        if (isset(self::MARKET_NAME_MAPPINGS[$id])) {
            return self::MARKET_NAME_MAPPINGS[$id];
        }

        throw new InvalidArgumentException("Cannot convert market id ${id} to name");
    }

    protected function convertToMarketId(string $name) : int
    {
        if (isset(self::MARKET_ID_MAPPINGS[$name])) {
            return self::MARKET_ID_MAPPINGS[$name];
        }

        throw new InvalidArgumentException("Cannot convert market name ${name} to id");
    }

    protected function initialize($params) : void
    {
        $this->setSerializer($params[0]);
    }

    public function getAllColumns() : array
    {
        return ['market', 'date_', 'description', 'link', 'hotspots', 'messages', 'coverstory'];
    }
}
