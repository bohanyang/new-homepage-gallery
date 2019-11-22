<?php

namespace App\Repository;

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
    )
    {
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
                $region = (int)$region;
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
        $query->matchesInQuery('image', $innerQuery)
            ->addDescend('date')
            ->addDescend('market')
            ->_include('image');

        $results = $query->find();

        if ($results === []) {
            return false;
        }

        $image = null;
        $archives = [];

        foreach ($results as $result) {
            if (!$result instanceof LeanObject) {
                return false;
            }

            $result = $result->toJSON();

            if (!isset($image)) {
                $image = $result['image'];
            }

            if ($image['objectId'] !== $result['image']['objectId']) {
                return false;
            }

            unset($result['image']);

            $archives[] = $result;
        }

        $image['archives'] = $archives;

        return $image;
    }
}
