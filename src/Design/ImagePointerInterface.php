<?php

namespace App\Design;

use App\NormalizedDate;

interface ImagePointerInterface
{
    public function getWp() : bool;

    public function getCopyright() : string;

    public function getLastAppearedOn() : ?NormalizedDate;

    public function setLastAppearedOn(NormalizedDate $date) : void;
}
