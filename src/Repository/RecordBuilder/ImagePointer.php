<?php

namespace App\Repository\RecordBuilder;

use DateTimeImmutable;
use DateTimeInterface;

interface ImagePointer
{
    public function getImage();

    public function getWp() : bool;

    public function getCopyright() : string;

    public function getLastAppearedOn() : ?DateTimeInterface;

    public function setLastAppearedOn(DateTimeImmutable $date) : void;
}
