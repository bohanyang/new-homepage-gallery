<?php

namespace App\Repository;

use DateTimeImmutable;
use InvalidArgumentException;
use LeanCloud\Client;
use LeanCloud\LeanObject;
use LeanCloud\Query;

class LeanCloudRepository implements RepositoryContract
{
    private const IMAGE_CLASS_NAME = 'Image';
    private const ARCHIVE_CLASS_NAME = 'Archive';

    public function __construct(
        string $appId,
        string $appKey,
        ?string $appMasterKey = null,
        ?string $apiServer = null,
        ?string $appEnv = null,
        ?string $region = null,
        ?string $kernelEnv = null,
        ?bool $debug = null
    ) {
        $appMasterKey = $appMasterKey ?? '';

        Client::initialize($appId, $appKey, $appMasterKey);
        Client::useMasterKey(false);

        if ($apiServer !== null) {
            Client::setServerUrl($apiServer);
        }

        $useProduction = $appEnv === null ? $kernelEnv === 'prod' : $appEnv === 'production';

        Client::useProduction($useProduction);

        if ($region !== null) {
            if (is_numeric($region)) {
                $region = (int) $region;
            }

            Client::useRegion($region);
        }

        $debug = $debug ?? !$useProduction;

        Client::setDebug($debug);
    }

    public function getImage(string $name)
    {
        $innerQuery = new Query(self::IMAGE_CLASS_NAME);
        $innerQuery->equalTo('name', $name);

        $query = new Query(self::ARCHIVE_CLASS_NAME);
        $results = $query
            ->matchesInQuery('image', $innerQuery)
            ->addDescend('date')
            ->addDescend('market')
            ->_include('image')
            ->find()
        ;

        if ($results === []) {
            throw new NotFoundException('Image not found');
        }

        $image = [];

        foreach ($results as $result) {
            $result = $result->toJSON();

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
        if ($limit < 1) {
            throw new InvalidArgumentException('The limit should be an integer greater than or equal to 1.');
        }

        if ($page < 1) {
            throw new InvalidArgumentException('The page number should be an integer greater than or equal to 1.');
        }

        $skip = $limit * ($page - 1);

        $query = new Query(self::IMAGE_CLASS_NAME);
        $results = $query
            ->limit($limit)
            ->skip($skip)
            ->addDescend('createdAt')
            ->find()
        ;

        if ($results === []) {
            throw new NotFoundException('No images found');
        }

        return array_map(
            function (LeanObject $object) {
                return $object->toJSON();
            }, $results
        );
    }

    public function getArchive(string $market, DateTimeImmutable $date)
    {
        $query = new Query(self::ARCHIVE_CLASS_NAME);

        $query
            ->equalTo('market', $market)
            ->equalTo('date', $date->format('Ymd'))
            ->_include('image');

        $result = $query->first();

        if ($result === null) {
            throw new NotFoundException('Archive not found');
        }

        return $result->toJSON();
    }

    public function findArchivesByDate(DateTimeImmutable $date)
    {
        $query = new Query(self::ARCHIVE_CLASS_NAME);

        $query
            ->equalTo('date', $date->format('Ymd'))
            ->addDescend('market')
            ->_include('image');

        $results = $query->find();

        if ($results === []) {
            throw new NotFoundException('No archives found');
        }

        $images = [];

        foreach ($results as $result) {
            $result = $result->toJSON();
            $imageId = $result['image']['objectId'];

            if (!isset($images[$imageId])) {
                $images[$imageId] = $result['image'];
            }

            unset($result['image']);

            $images[$imageId]['archives'][] = $result;
        }

        return $images;
    }
}
