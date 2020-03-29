<?php

namespace App\Design;

interface RecordInterface
{
    public function setData(Record $data) : void;

    public function save() : void;
}
