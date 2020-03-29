<?php

namespace App\Repository\LeanCloud;

use App\Repository\RecordInterface;
use App\Repository\Struct\Record as Data;
use LeanCloud\LeanObject;

class Record implements RecordInterface
{
    /** @var LeanObject */
    private $object;

    /** @var Data */
    private $data;

    public function __construct(Data $data)
    {
        $this->data = $data;
    }

    public function setImagePointer()
    {
        $this->object->set('image', );
    }
}