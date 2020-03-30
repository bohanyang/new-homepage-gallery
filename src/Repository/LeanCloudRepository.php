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

class LeanCloudRepository
{
    use RepositoryTrait;

    public function __construct(LeanCloud $LeanCloud)
    {
        Image::registerClass();
        Record::registerClass();
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

            $images[$id]['records'][] = $record->toModelParams();
        }

        foreach ($images as $id => $image) {
            $images[] = new ImageView($image);
            unset($images[$id]);
        }

        return $images;
    }

    public function findMarketsHaveArchiveOfDate(DateTimeInterface $date, array $markets)
    {
        $query = new Query(Record::CLASS_NAME);
        $query->equalTo('date', $date->format('Ymd'))->containedIn('market', $markets);
        $results = [];

        /** @var LeanObject $result */
        foreach ($query->find() as $result) {
            $results[] = $result->get('market');
        }

        return $results;
    }

    public function save(RecordModel $record, ImageModel $image) : void
    {
        $image = $this->findOrCreateImage($record, $image);
        $record = $this->createRecord($record, $image);
        $duplicates = $this->findDuplicateRecord($record);
        if ($duplicates !== []) {
            throw new InvalidArgumentException('Duplicate record found');
        }
        LeanObject::saveAll([$record]);
    }

    private function findOrCreateImage(RecordModel $record, ImageModel $image) : Image
    {
        $query = new Query(Image::CLASS_NAME);
        $query->equalTo('name', $image->name);
        $object = $query->find();

        if ($object === []) {
            $object = new Image();

            foreach ($image as $field => $value) {
                if ($value !== null) {
                    $object->set($field, $value);
                }
            }

            $object->set('available', false);
            /*
            $date = $record->date->get();
            $object->set('firstAppearedOn', $date);
            $object->set('lastAppearedOn', $date);
            */

            return $object;
        }

        $object = $object[0];
        $this->referExistingImage($record, $image, $object);

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

        $object->set('date', $record->date->get()->format('Ymd'));
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
}
