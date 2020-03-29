<?php

namespace App\Repository\RecordBuilder;

use App\NormalizedDate;
use App\Repository\LeanCloudRepository;
use App\Repository\RepositoryInterface;
use DateTimeImmutable;
use DateTimeInterface;
use LeanCloud\LeanObject;
use UnexpectedValueException;

abstract class AbstractRecordBuilder
{
    protected $response;
    /** @var DateTimeImmutable */
    protected $date;
    /** @var RepositoryInterface */
    protected $repository;

    public function get($response)
    {
        $this->response = $response;
    }

    public function getRecord()
    {
        $this->date = NormalizedDate::fromDate($this->response['date'])->get();
        $this->setArchive();
        $this->setImagePointer();
    }

    protected function getImagePointer()
    {
        $image = $this->repository->findImage($this->response['image']['name']);
        if ($image === null) {
            return $this->createImage();
        }
        if ($image->getWp() !== $this->response['image']['wp'] ||
            $image->getCopyright() !== $this->response['image']['copyright']
        ) {
            throw new UnexpectedValueException('Image does not match the existing one');
        }
        $lastAppearedOn = $image->getLastAppearedOn();
        if ($lastAppearedOn === null || $this->date > $lastAppearedOn) {
            $image->setLastAppearedOn($this->date);
        }
        return $image->getImage();
    }

    abstract protected function createImage();
    abstract protected function setArchive() : void;
    abstract protected function setImagePointer() : void;
}