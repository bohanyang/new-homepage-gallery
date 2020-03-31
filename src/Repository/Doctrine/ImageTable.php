<?php

namespace App\Repository\Doctrine;

class ImageTable extends AbstractTable
{
    use HexToBinaryTrait;
    use NormalizeDateTrait;
    use SerializeTrait;

    protected const NAME = 'images';

    protected const COLUMNS = [
        //'id' => 'integer',
        'id' => 'binary',
        'name' => 'string',
        'first_appeared_on' => 'date_immutable',
        'last_appeared_on' => 'date_immutable',
        'urlbase' => 'string',
        'copyright' => 'string',
        'wp' => 'boolean',
        'vid' => 'blob',
    ];

    protected $indexes = [
        [self::PRIMARY_KEY_INDEX, ['id']],
        [self::UNIQUE_INDEX, ['name']]
    ];

    protected $columnOptions = [
        //'id' => ['autoincrement' => true, 'unsigned' => true],
        //'id' => ['length' => 12, 'fixed' => true, 'customSchemaOptions' => ['unique' => true]],
        'id' => ['length' => 12, 'fixed' => true],
        'name' => ['length' => 255],
        'first_appeared_on' => ['notnull' => false],
        'last_appeared_on' => ['notnull' => false],
        'urlbase' => ['length' => 255],
        'copyright' => ['length' => 255],
        'vid' => ['notnull' => false, 'length' => 65535]
    ];

    protected $queryCallbacks = [
        'id' => 'hex2bin',
        'first_appeared_on' => 'getNormalizedDate',
        'last_appeared_on' => 'getNormalizedDate',
        'vid' => 'serialize',
    ];

    protected $resultCallbacks = [
        //'id' => 'convertToPHPValue',
        'id' => 'bin2hex',
        'first_appeared_on' => 'normalizeDate',
        'last_appeared_on' => 'normalizeDate',
        'wp' => 'convertToPHPValue',
        'vid' => 'deserialize'
    ];

    protected function initialize($params) : void
    {
        $this->setSerializer($params[0]);
    }

    public function getAllColumns() : array
    {
        return ['name', 'urlbase', 'copyright', 'wp', 'vid'];
    }
}
