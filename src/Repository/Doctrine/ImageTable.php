<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Types\Types;

class ImageTable extends AbstractTable
{
    protected const NAME = 'images';

    protected const COLUMNS = [
        'id' => ObjectIdType::NAME,
        'name' => Types::STRING,
        'first_appeared_on' => DateType::NAME,
        'last_appeared_on' => DateType::NAME,
        'urlbase' => Types::STRING,
        'copyright' => Types::STRING,
        'wp' => Types::BOOLEAN,
        'vid' => SerializedBinaryType::NAME,
    ];

    protected $indexes = [
        [self::PRIMARY_KEY_INDEX, ['id']],
        [self::UNIQUE_INDEX, ['name']]
    ];

    protected $columnOptions = [
        'name' => ['length' => 255],
        //'first_appeared_on' => ['notnull' => false],
        //'last_appeared_on' => ['notnull' => false],
        'urlbase' => ['length' => 255],
        'copyright' => ['length' => 255],
        'vid' => ['notnull' => false, 'length' => 65535]
    ];

    protected function initialize($params) : void
    {
    }

    public function getAllColumns() : array
    {
        return ['name', 'urlbase', 'copyright', 'wp', 'vid'];
    }
}
