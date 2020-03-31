<?php

namespace App\Repository;

use App\Model\Date;

class DoctrineImagePointer implements ImagePointerInterface
{
    /** @var DoctrineRepository */
    private $repository;

    /** @var array */
    private $data;

    public function __construct(DoctrineRepository $repository, array $data)
    {
        $this->repository = $repository;
        $this->data = $data;
    }

    public function getWp() : bool
    {
        return $this->data['wp'];
    }

    public function getCopyright() : string
    {
        return $this->data['copyright'];
    }

    public function getLastAppearedOn() : ?Date
    {
        return $this->data['last_appeared_on'] ?? null;
    }

    public function setLastAppearedOn(Date $date) : void
    {
        $this->data['last_appeared_on'] = $date;
        // todo: update SQL
    }
}
