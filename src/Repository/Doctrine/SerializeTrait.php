<?php

namespace App\Repository\Doctrine;

use function is_resource;
use function Safe\fclose;
use function Safe\rewind;
use function Safe\stream_get_contents;

trait SerializeTrait
{
    /** @var SerializerInterface */
    private $serializer;

    protected function serialize($data) : string
    {
        return $this->serializer->serialize($data);
    }

    /**
     * @param string|resource $data
     * @return mixed
     * @throws \Safe\Exceptions\FilesystemException
     * @throws \Safe\Exceptions\StreamException
     */
    protected function deserialize($data)
    {
        if (is_resource($data)) {
            $resource = $data;
            rewind($resource);
            $data = stream_get_contents($resource);
            fclose($resource);
        }

        return $this->serializer->deserialize($data);
    }

    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }
}
