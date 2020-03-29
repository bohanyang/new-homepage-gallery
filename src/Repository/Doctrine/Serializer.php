<?php

namespace App\Repository\Doctrine;

class Serializer implements SerializerInterface
{
    public function serialize($data) : string
    {
        return lzf_compress(igbinary_serialize($data));
    }

    public function deserialize(string $data)
    {
        return igbinary_unserialize(lzf_decompress($data));
    }
}
