<?php

namespace App\Replicator;

use App\Repository\Doctrine\ImageTable;
use App\Repository\Doctrine\InsertTrait;
use App\Repository\Doctrine\RecordTable;
use App\Repository\Doctrine\SchemaTrait;
use App\Repository\LeanCloud\Image;
use App\Repository\LeanCloud\Record;
use Doctrine\DBAL\Connection;
use LeanCloud\LeanObject;
use RuntimeException;

class LeanCloudDoctrineReplicator
{
    use InsertTrait;
    use SchemaTrait;

    /** @var ImageTable */
    private $imageTable;

    /** @var RecordTable */
    private $recordTable;

    /** @var Connection */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
        $this->register($conn->getDatabasePlatform());
        $this->imageTable->setColumnNameMappings(
            [
                'objectId' => 'id',
                'lastAppearedOn' => 'last_appeared_on',
                'firstAppearedOn' => 'first_appeared_on',
            ]
        );
        $this->recordTable->setColumnNameMappings(
            [
                'objectId' => 'id',
                'date' => 'date_',
                'image' => 'image_id',
                'info' => 'description',
                'hs' => 'hotspots',
                'msg' => 'messages',
                'cs' => 'coverstory'
            ]
        );

        Image::registerClass();
        Record::registerClass();
    }

    public function importRecord(Record $record)
    {
        $this->insertRecord(self::convertRecord($record));
    }

    public function importImage(Image $image)
    {
        $this->insertImage(self::convertImage($image));
    }

    private function createLeanObject(string $class, array $data)
    {
        $object = LeanObject::create($class);
        $object->mergeAfterSave($data);

        return $object;
    }

    public function importImageArray(array $data)
    {
        $data = $this->createLeanObject(Image::CLASS_NAME, $data);

        /** @var Image $data */
        $this->importImage($data);
    }

    public function importRecordArray(array $data)
    {
        $data = $this->createLeanObject(Record::CLASS_NAME, $data);

        /** @var Record $data */
        $this->importRecord($data);
    }

    private static function convertImage(Image $image)
    {
        $data = $image->toJSON();
        $data['lastAppearedOn'] = $image->getLastAppearedOn();
        $data['firstAppearedOn'] = $image->getFirstAppearedOn();
        unset($data['createdAt'], $data['updatedAt'], $data['ACL']);

        return $data;
    }

    private static function convertRecord(Record $record)
    {
        $data = $record->toJSON();
        $data['date'] = $record->getDate();
        $data['image'] = $data['image']['objectId'];
        unset($data['createdAt'], $data['updatedAt'], $data['ACL']);

        return $data;
    }

    public function getConnection() : Connection
    {
        return $this->conn;
    }
}
