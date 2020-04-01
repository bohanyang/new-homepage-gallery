<?php

namespace App\Replicator;

use App\Repository\LeanCloud\Record;
use App\Repository\DoctrineRepository;
use App\Repository\LeanCloud\Image;
use App\Repository\LeanCloudRepository;

class LeanCloudDoctrineReplicator
{
    /** @var LeanCloudRepository */
    private $source;

    /** @var DoctrineRepository */
    private $destination;

    public function __construct(LeanCloudRepository $source, DoctrineRepository $destination)
    {
        $this->source = $source;
        $this->destination = $destination;
    }

    public function getDoctrine() : DoctrineRepository
    {
        return $this->destination;
    }

    public function importRecord(Record $record)
    {
        $this->destination->insertRecord(self::convertRecord($record));
    }

    public function importImage(Image $image)
    {
        $this->destination->insertImage(self::convertImage($image));
    }

    private static function convertImage(Image $image)
    {
        $data = $image->toModelParams();
        $data['id'] = $image->getObjectId();
        $data['last_appeared_on'] = $image->getLastAppearedOn();
        $data['first_appeared_on'] = $image->getFirstAppearedOn();

        return $data;
    }

    private static function convertRecord(Record $record)
    {
        $data = $record->toModelParams();
        $data['id'] = $record->getObjectId();
        $data['image_id'] = $record->get('image')->getObjectId();

        return $data;
    }
}
