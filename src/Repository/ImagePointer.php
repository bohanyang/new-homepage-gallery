<?php

namespace App\Repository;

use App\Model\Date;

interface ImagePointer
{
    public function getWp() : bool;

    public function getCopyright() : string;

    public function getLastAppearedOn() : ?Date;

    public function setLastAppearedOn(Date $date) : void;
}
