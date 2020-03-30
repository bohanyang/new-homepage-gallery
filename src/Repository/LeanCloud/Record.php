<?php

namespace App\Repository\LeanCloud;

use App\Helper;
use App\Model\Date;
use LeanCloud\LeanObject;

final class Record extends LeanObject
{
    use LeanObjectTrait;

    public const CLASS_NAME = 'Archive';

    protected static $className = self::CLASS_NAME;

    private const FIELD_MAPPINGS = [
        'info' => 'description',
        'hs' => 'hotspots',
        'msg' => 'messages',
        'cs' => 'coverstory'
    ];

    public function toModelParams() : array
    {
        $data = $this->getData();
        unset($data['image']);
        $data = Helper::array_replace_keys($data, self::FIELD_MAPPINGS);
        $data['date'] = Date::createFromYmd($data['date']);

        return $data;
    }
}