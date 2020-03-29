<?php


namespace App\Design;


class DoctrineRecord extends AbstractRecord
{
    /** @var DoctrineRepository */
    private $repository;

    /** @var string */
    private $imageId;

    public function __construct(DoctrineRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function getImagePointer() : ?DoctrineImagePointer
    {
        $image = $this->repository->findImage($this->data->image->name);

        if ($image !== null) {
            $this->imageId = $image['id'];
            $image = new DoctrineImagePointer($image, $this->repository);
        }

        return $image;
    }

    protected function createImage() : void
    {
        $this->imageId = $this->repository->createImage($this->data->image);
    }

    public function save() : void
    {

    }
}