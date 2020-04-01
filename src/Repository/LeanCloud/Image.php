<?php

namespace App\Repository\LeanCloud;

use App\Model\Date;
use App\Repository\ImagePointerInterface;
use LeanCloud\LeanObject;

final class Image extends LeanObject implements ImagePointerInterface
{
    use LeanObjectTrait;

    public const CLASS_NAME = 'Image';

    protected static $className = self::CLASS_NAME;

    public function toModelParams() : array
    {
        $data = $this->getData();
        unset($data['available'], $data['lastAppearedOn'], $data['firstAppearedOn']);

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

        return $date === null ? $date : Date::createFromUTC($date);
    }

    public function getFirstAppearedOn() : ?Date
    {
        $date = $this->get('firstAppearedOn');

        return $date === null ? $date : Date::createFromUTC($date);
    }

    public function setLastAppearedOn(Date $date) : void
    {
        $this->set('lastAppearedOn', $date->get());
    }

    public function setFirstAppearedOn(Date $date) : void
    {
        $this->set('firstAppearedOn', $date->get());
    }
}
