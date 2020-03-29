<?php

namespace App\Design;

use LeanCloud\LeanObject;

class LeanCloudRecord extends AbstractRecord
{
    /** @var LeanCloudRepository */
    private $repository;

    /** @var LeanObject */
    private $image;

    public function __construct(LeanCloudRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getImagePointer() : ?LeanCloudImagePointer
    {
        $image = $this->repository->findImage($this->data->image->name);

        if ($image !== null) {
            $image = new LeanCloudImagePointer($this->image = $image);
        }

        return $image;
    }

    protected function createImage() : void
    {
        $this->image = $this->repository->createImage($this->data->image);
    }

    public function save() : void
    {
        $this->repository->save($this->data, $this->image);
    }
}
