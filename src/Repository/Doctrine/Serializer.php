<?php

namespace App\Repository\Doctrine;

class Serializer implements SerializerInterface
{
    public function serialize($data) : string
    {
        return /** @scrutinizer ignore-call */ lzf_compress(igbinary_serialize($data));
    }

    public function deserialize(string $data)
    {
        return igbinary_unserialize(/** @scrutinizer ignore-call */ lzf_decompress($data));
    }
}
