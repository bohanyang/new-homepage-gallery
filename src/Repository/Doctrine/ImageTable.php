<?php

namespace App\Repository\Doctrine;

class ImageTable extends AbstractTable
{
    use HexToBinaryTrait;
    use SerializeTrait;

    protected const NAME = 'images';

    protected const COLUMNS = [
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

    protected $indexes = [
        [self::PRIMARY_KEY_INDEX, ['id']]
    ];

    protected $columnOptions = [
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

    protected $queryCallbacks = [
        'id' => 'hex2bin',
        'vid' => 'serialize',
    ];

    protected $resultCallbacks = [
        //'id' => 'convertToPHPValue',
        'id' => 'bin2hex',
        'vid' => 'deserialize',
        'wp' => 'convertToPHPValue'
    ];

    protected function initialize($params) : void
    {
        $this->setSerializer($params[0]);
    }
}
