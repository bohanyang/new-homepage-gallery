<?php

namespace App\Repository\LeanCloud;

use App\Model\Date;
use App\Repository\ImagePointer;
use LeanCloud\LeanObject;

final class Image extends LeanObject implements ImagePointer
{
    use LeanObjectTrait;

    public const CLASS_NAME = 'Image';

    protected static $className = self::CLASS_NAME;

    public function toModelParams() : array
    {
        $data = $this->getData();
        unset($data['available']);

        return $data;
    }

    public function getWp() : bool
    {
        return $this->get('wp');
    }

    public function getCopyright() : string
    {
        return $this->get('copyright');
    }

    public function getLastAppearedOn() : ?Date
    {
        $date = $this->get('lastAppearedOn');

        if ($date === null) {
            return $date;
        }

        return Date::createFromUTC($date);
    }

    public function setLastAppearedOn(Date $date) : void
    {
        $this->set('lastAppearedOn', $date->get());
    }
}
