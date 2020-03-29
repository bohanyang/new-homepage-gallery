<?php

namespace App\Design;

use App\NormalizedDate;
use LeanCloud\LeanObject;

class LeanCloudImagePointer implements ImagePointerInterface
{
    /** @var LeanObject */
    private $object;

    public function __construct(LeanObject $object)
    {
        $this->object = $object;
    }

    public function getWp() : bool
    {
        return $this->object->get('wp');
    }

    public function getCopyright() : string
    {
        return $this->object->get('copyright');
    }

    public function getLastAppearedOn() : ?NormalizedDate
    {
        $date = $this->object->get('lastAppearedOn');

        if ($date === null) {
            return $date;
        }

        return NormalizedDate::fromTimestamp($date);
    }

    public function setLastAppearedOn(NormalizedDate $date) : void
    {
        $this->object->set('lastAppearedOn', $date->get());
    }
}
