<?php


namespace App\Design;


use App\NormalizedDate;

class DoctrineImagePointer implements ImagePointerInterface
{
    /** @var array */
    private $result;

    /** @var DoctrineRepository */
    private $repository;

    public function __construct(array $result, DoctrineRepository $repository)
    {
        $this->result = $result;
        $this->repository = $repository;
    }

    public function getWp() : bool
    {
        return $this->result['wp'];
    }

    public function getCopyright() : string
    {
        return $this->result['copyright'];
    }

    public function getLastAppearedOn() : ?NormalizedDate
    {
        return $this->result['last_appeared_on'];
    }

    public function setLastAppearedOn(NormalizedDate $date) : void
    {
        $this->repository->updateLastAppearedOn($this->result['id'], $date->get());
    }
}