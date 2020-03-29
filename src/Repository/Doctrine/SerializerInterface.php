<?php

namespace App\Repository\Doctrine;

interface SerializerInterface
{
    public function serialize($data) : string;

    public function deserialize(string $data);
}
