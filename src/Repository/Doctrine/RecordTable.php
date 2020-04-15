<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Types\Types;

class RecordTable extends AbstractTable
{
    protected const NAME = 'records';

    protected const COLUMNS = [
        'id' => ObjectIdType::NAME,
        'market' => MarketType::NAME,
        'date_' => DateType::NAME,
        'image_id' => ObjectIdType::NAME,
        'description' => Types::TEXT,
        'link' => Types::STRING,
        'hotspots' => SerializedBinaryType::NAME,
        'messages' => SerializedBinaryType::NAME,
        'coverstory' => SerializedBinaryType::NAME,
    ];

    protected $indexes = [
        [self::PRIMARY_KEY_INDEX, ['id']],
        [self::NORMAL_INDEX, ['date_']],
        [self::NORMAL_INDEX, ['image_id']],
        [self::NORMAL_INDEX, ['market', 'date_']]
    ];

    protected $columnOptions = [
        'description' => ['length' => 65535],
        'link' => ['notnull' => false, 'length' => 2083],
        'hotspots' => ['notnull' => false, 'length' => 65535],
        'messages' => ['notnull' => false, 'length' => 65535],
        'coverstory' => ['notnull' => false, 'length' => 65535]
    ];

    public const FIELD_MAPPINGS = [
        'date_' => 'date'
    ];

    public const COLUMN_NAME_MAPPINGS = [
        'date' => 'date_'
    ];

    protected function initialize($params) : void
    {
    }

    public function getAllColumns() : array
    {
        return ['market', 'date_', 'description', 'link', 'hotspots', 'messages', 'coverstory'];
    }
}
