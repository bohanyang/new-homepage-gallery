<?php

namespace App\Repository;

use App\LeanCloud;
use App\Model\Date;
use App\Model\Image as ImageModel;
use App\Model\ImageView;
use App\Model\Record as RecordModel;
use App\Model\RecordView;
use App\Repository\LeanCloud\Image;
use App\Repository\LeanCloud\Record;
use DateTimeInterface;
use InvalidArgumentException;
use LeanCloud\ACL;
use LeanCloud\LeanObject;
use LeanCloud\Query;
use Psr\Log\LoggerInterface;

class LeanCloudRepository implements RepositoryInterface
{
    use ReferExistingImageTrait;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(LeanCloud $LeanCloud, LoggerInterface $logger)
    {
        Image::registerClass();
        Record::registerClass();

        $this->logger = $logger;
    }

    private static function createACL() : ACL
    {
        $ACL = new ACL();
        $ACL->setPublicReadAccess(true);
        $ACL->setPublicWriteAccess(true);

        return $ACL;
    }

    public function getImage(string $name) : ImageView
    {
        $imageQuery = new Query(Image::CLASS_NAME);
        $imageQuery->equalTo('name', $name);

        $query = new Query(Record::CLASS_NAME);
        $query->matchesInQuery('image', $imageQuery)->addDescend('date')->_include('image');

        /** @var Record[] $records */
        $records = $query->find();

        if ($records === []) {
            throw NotFoundException::image($name);
        }

        /** @var Image $image */
        $image = $records[0]->get('image');
        $image = $image->toModelParams();

        foreach ($records as $record) {
            $image['records'][] = $record->toModelParams();
        }

        return new ImageView($image);
    }

    public function listImages(int $limit, int $skip = 0) : array
    {
        $query = new Query(Image::CLASS_NAME);
        $query->limit($limit)->skip($skip)->addDescend('createdAt');

        /** @var Image[] $images */
        $images = $query->find();

        if ($images === []) {
            throw NotFoundException::images();
        }

        foreach ($images as $i => $image) {
            $images[$i] = new ImageModel($image->toModelParams());
        }

        return $images;
    }

    public function getRecord(string $market, Date $date) : RecordView
    {
        $query = new Query(Record::CLASS_NAME);
        $query->equalTo('market', $market)->equalTo('date', $date->get()->format('Ymd'))->_include('image');

        $record = $query->find();

        if ($record === []) {
            throw NotFoundException::record($market, $date);
        }

        /** @var Record $record */
        $record = $record[0];

        /** @var Image $image */
        $image = $record->get('image');

        $record = $record->toModelParams();
        $record['image'] = $image->toModelParams();

        return new RecordView($record);
    }

    public function findImagesByDate(Date $date) : array
    {
        $query = new Query(Record::CLASS_NAME);
        $query->equalTo('date', $date->get()->format('Ymd'))->descend('market')->_include('image');

        /** @var Record[] $records */
        $records = $query->find();

        if ($records === []) {
            throw NotFoundException::date($date);
        }

        $images = [];

        foreach ($records as $record) {
            /** @var Image $image */
            $image = $record->get('image');
            $id = $image->get('objectId');

            if (!isset($images[$id])) {
                $images[$id] = $image->toModelParams();
            }

            $images[$id]['records'][] = new RecordModel($record->toModelParams());
        }

        foreach ($images as $id => $image) {
            $images[] = new ImageView($image);
            unset($images[$id]);
        }

        return $images;
    }

    public function findMarketsHaveRecordOn(Date $date, array $markets) : array
    {
        $query = new Query(Record::CLASS_NAME);
        $query->equalTo('date', $date->get()->format('Ymd'))->containedIn('market', $markets);
        $results = $query->find();

        /** @var Record $result */
        foreach ($results as $i => $result) {
            $results[$i] = $result->get('market');
        }

        return $results;
    }

    public function save(RecordModel $record, ImageModel $image) : void
    {
        $this->logger->debug("Got record {$record->market} {$record->date->get()->format('Y/n/j')}");
        $image = $this->findOrCreateImage($image, $record);
        $record = $this->createRecord($record, $image);
        $results = $this->findDuplicateRecord($record);
        if ($results !== []) {
            /** @var Record $result */
            foreach ($results as $result) {
                $this->logger->critical("Duplicate record: {$result->getObjectId()}");
            }
            throw new InvalidArgumentException('Duplicate record found');
        }
        LeanObject::saveAll([$record]);
    }

    private function findOrCreateImage(ImageModel $image, RecordModel $record) : Image
    {
        $query = new Query(Image::CLASS_NAME);
        $query->equalTo('name', $image->name);
        $object = $query->find();

        if ($object === []) {
            $this->logger->debug('Create Image: ' . $image->name);
            $object = new Image();

            foreach ($image as $field => $value) {
                if ($value !== null) {
                    $object->set($field, $value);
                }
            }

            $object->set('available', false);
            $object->setFirstAppearedOn($record->date);
            $object->setLastAppearedOn($record->date);

            return $object;
        }

        /** @var Image $object */
        $object = $object[0];

        $this->logger->debug("Refer Existing Image: {$image->name} ({$object->getObjectId()})");
        $this->referExistingImage($object, $image, $record);

        return $object;
    }

    private function createRecord(RecordModel $record, Image $image) : Record
    {
        $fieldMappings = [
            'description' => 'info',
            'hotspots' => 'hs',
            'messages' => 'msg',
            'coverstory' => 'cs'
        ];

        $object = new Record();

        foreach ($record as $field => $value) {
            if (
                $field !== 'date' &&
                $value !== null
            ) {
                $field = $fieldMappings[$field] ?? $field;
                $object->set($field, $value);
            }
        }

        $object->setDate($record->date);
        $object->set('image', $image);

        return $object;
    }

    private function findDuplicateRecord(Record $record) : array
    {
        $marketQuery = new Query(Record::CLASS_NAME);
        $marketQuery->equalTo('market', $record->get('market'));
        $dateQuery = new Query(Record::CLASS_NAME);
        $dateQuery->equalTo('date', $record->get('date'));
        $imageSubQuery = new Query(Image::CLASS_NAME);
        $imageSubQuery->equalTo('name', $record->get('image')->get('name'));
        $imageQuery = new Query(Record::CLASS_NAME);
        $imageQuery->matchesInQuery('image', $imageSubQuery);
        $orQuery = Query::orQuery($dateQuery, $imageQuery);
        $query = Query::andQuery($marketQuery, $orQuery);

        return $query->find();
    }

    public function export(string $class, int $limit, int $skip = 0) : array
    {
        $query = new Query($class);
        $query->limit($limit)->skip($skip)->ascend('objectId');

        return $query->find();
    }
}
