<?php

namespace App\Repository\Doctrine;

trait SerializeTrait
{
    /** @var SerializerInterface */
    private $serializer;

    protected function serialize($data) : string
    {
        return $this->serializer->serialize($data);
    }

    protected function deserialize(string $data)
    {
        return $this->serializer->deserialize($data);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
}
