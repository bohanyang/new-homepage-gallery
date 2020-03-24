<?php

namespace App\Repository;

use App\Date;
use App\LeanCloud;
use App\Repository\RecordBuilder\LeanObjectImagePointer;
use DateTime;
use Safe\DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use LeanCloud\ACL;
use LeanCloud\LeanObject;
use LeanCloud\Query;
use UnexpectedValueException;

class LeanCloudRepository implements RepositoryContract
{
    use RepositoryTrait;

    public const IMAGE_CLASS_NAME = 'Image';
    public const ARCHIVE_CLASS_NAME = 'Archive';

    /** @var ACL $acl */
    private $acl;

    public function __construct(LeanCloud $lc)
    {
        $acl = new ACL();
        $acl->setPublicReadAccess(true);
        $acl->setPublicWriteAccess(true);
        $this->acl = $acl;
    }

    public function getImage(string $name)
    {
        $innerQuery = new Query(self::IMAGE_CLASS_NAME);
        $innerQuery->equalTo('name', $name);

        $query = new Query(self::ARCHIVE_CLASS_NAME);
        $query->matchesInQuery('image', $innerQuery)->addDescend('date')->_include('image');
        $results = $query->find();

        if ($results === []) {
            throw new NotFoundException('Image not found');
        }

        $image = [];

        foreach ($results as $result) {
            $result = self::transform($result);

            if ($image === []) {
                $image = $result['image'];
            }

            unset($result['image']);

            $image['archives'][] = $result;
        }

        return $image;
    }

    public function listImages(int $limit, int $page)
    {
        $skip = $this->getSkip($limit, $page);

        $query = new Query(self::IMAGE_CLASS_NAME);
        $query->limit($limit)->skip($skip)->addDescend('createdAt');
        $results = $query->find();

        if ($results === []) {
            throw new NotFoundException('No images found');
        }

        return array_map(
            function (LeanObject $object) {
                return $object->toJSON();
            },
            $results
        );
    }

    public function getArchive(string $market, DateTimeInterface $date)
    {
        $query = new Query(self::ARCHIVE_CLASS_NAME);
        $query->equalTo('market', $market)->equalTo('date', $date->format('Ymd'))->_include('image');
        $result = $query->first();

        if ($result === null) {
            throw new NotFoundException('Archive not found');
        }

        return self::transform($result);
    }

    public function findArchivesByDate(DateTimeInterface $date)
    {
        $query = new Query(self::ARCHIVE_CLASS_NAME);
        $query->equalTo('date', $date->format('Ymd'))->_include('image');
        $results = $query->find();

        if ($results === []) {
            throw new NotFoundException('No archives found');
        }

        $images = [];

        foreach ($results as $result) {
            $result = self::transform($result);
            $imageId = $result['image']['objectId'];

            if (!isset($images[$imageId])) {
                $images[$imageId] = $result['image'];
            }

            unset($result['image']);

            $images[$imageId]['archives'][] = $result;
        }

        return $images;
    }

    public function findMarketsHaveArchiveOfDate(DateTimeInterface $date, array $markets)
    {
        $query = new Query(self::ARCHIVE_CLASS_NAME);
        $query->equalTo('date', $date->format('Ymd'))->containedIn('market', $markets);
        $results = [];

        /** @var LeanObject $result */
        foreach ($query->find() as $result) {
            $results[] = $result->get('market');
        }

        return $results;
    }

    public function exportImages(int $skip, int $limit)
    {
        $query = new Query(self::IMAGE_CLASS_NAME);
        $results = $query->find($skip, $limit);
        foreach ($results as $i => $result) {
            /** @var LeanObject $result */
            $result = $result->toJSON();
            $result['id'] = $result['objectId'];
            unset($result['objectId'], $result['ACL'], $result['createdAt'], $result['updatedAt']);
            $results[$i] = $result;
        }
        return $results;
    }

    public function exportArchives(int $skip, int $limit)
    {
        $query = new Query(self::ARCHIVE_CLASS_NAME);
        $results = $query->find($skip, $limit);
        foreach ($results as $i => $result) {
            /** @var LeanObject $result */
            $result = self::transform($result);
            $result['id'] = $result['objectId'];
            $result['image_id'] = $result['image']['objectId'];
            unset($result['image'], $result['objectId'], $result['ACL'], $result['createdAt'], $result['updatedAt']);
            $results[$i] = $result;
        }
        return $results;
    }

    private static function arrayReplaceKeys($arr, $keyMap)
    {
        $result = [];

        foreach ($arr as $key => $val) {
            $key = $keyMap[$key] ?? $key;
            $result[$key] = $val;
        }

        return $result;
    }

    private const FIELD_NAMES = [
        'archives' => [
            'info' => 'description',
            'hs' => 'hotspots',
            'msg' => 'messages',
            'cs' => 'coverstory'
        ]
    ];

    private static function transform(LeanObject $result)
    {
        $archive = self::arrayReplaceKeys($result->toJSON(), self::FIELD_NAMES['archives']);
        $archive['date'] = DateTimeImmutable::createFromFormat(
            'YmdHis',
            "{$archive['date']}000000",
            new DateTimeZone('UTC')
        );

        return $archive;
    }

    public function save(array $data)
    {
        $data['image'] = $this->getImageObject($data['image']);
        $data = $this->getArchiveObject($data);
        LeanObject::saveAll([$data]);
    }

    public function findDuplicatedArchive(string $market, string $date, string $image)
    {
        $marketQuery = new Query(self::ARCHIVE_CLASS_NAME);
        $marketQuery->equalTo('market', $market);
        $dateQuery = new Query(self::ARCHIVE_CLASS_NAME);
        $dateQuery->equalTo('date', $date);
        $innerQuery = new Query(self::IMAGE_CLASS_NAME);
        $innerQuery->equalTo('name', $image);
        $imageQuery = new Query(self::ARCHIVE_CLASS_NAME);
        $imageQuery->matchesInQuery('image', $innerQuery);
        $orQuery = Query::orQuery($dateQuery, $imageQuery);
        $query = Query::andQuery($marketQuery, $orQuery);

        return $query->find();
    }

    private function getArchiveObject(array $data) : LeanObject
    {
        $date = $data['date']->format('Ymd');
        /** @var LeanObject $image */
        $image = $data['image'];
        $results = $this->findDuplicatedArchive($data['market'], $date, $image->get('name'));
        if ($results !== []) {
            throw new UnexpectedValueException('Duplicated archive found');
        }
        $object = new LeanObject(self::ARCHIVE_CLASS_NAME);
        $object->setACL($this->acl);
        $object->set('market', $data['market']);
        $object->set('date', $date);
        $object->set('info', $data['description']);
        $object->set('image', $image);
        if (isset($data['link'])) {
            $object->set('link', $data['link']);
        }
        if (isset($data['hotspots'])) {
            $object->set('hs', $data['hotspots']);
        }
        if (isset($data['messages'])) {
            $object->set('msg', $data['messages']);
        }
        return $object;
    }

    public function findImage(string $name) : ?LeanObjectImagePointer
    {
        $query = new Query(self::IMAGE_CLASS_NAME);
        $results = $query->equalTo('name', $name)->limit(1)->find();

        return $results === [] ? null : new LeanObjectImagePointer($results[0]);
    }

    private function getImageObject(array $image)
    {
        $object = $this->findImage($image['name']);
        if ($object === null) {
            $object = new LeanObject(self::IMAGE_CLASS_NAME);
            $object->setACL($this->acl);
            $object->set('name', $image['name']);
            $object->set('urlbase', $image['urlbase']);
            $object->set('copyright', $image['copyright']);
            $object->set('wp', $image['wp']);
            $object->set('available', false);
            if (isset($image['vid'])) {
                $object->set('vid', $image['vid']);
            }
            return $object;
        }

        $object = $object->getImage();

        if ($image['copyright'] !== $object->get('copyright') || $image['wp'] !== $object->get('wp')) {
            throw new UnexpectedValueException('Image does not match the existing one');
        }

        return $object;
    }
}
