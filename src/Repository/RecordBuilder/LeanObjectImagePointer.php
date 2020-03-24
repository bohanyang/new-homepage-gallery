<?php


namespace App\Repository\RecordBuilder;


use App\Date;
use DateTimeImmutable;
use DateTimeInterface;
use LeanCloud\LeanObject;

class LeanObjectImagePointer implements ImagePointer
{
    private $object;

    public function __construct(LeanObject $object)
    {
        $this->object = $object;
    }

    public function getImage() : LeanObject
    {
        return $this->object;
    }

    public function getWp() : bool
    {
        return $this->object->get('wp');
    }

    public function getCopyright() : string
    {
        return $this->object->get('copyright');
    }

    public function getLastAppearedOn() : ?DateTimeInterface
    {
        $lastAppearedOn = $this->object->get('lastAppearedOn');

        if ($lastAppearedOn === null) {
            return $lastAppearedOn;
        }

        return Date::fromTimestamp($lastAppearedOn)->get();
    }

    public function setLastAppearedOn(DateTimeImmutable $date) : void
    {
        $this->object->set('lastAppearedOn', $date);
    }
}