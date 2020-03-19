<?php

namespace App;

use LeanCloud\Client;
use LeanCloud\Storage\IStorage;
use LeanCloud\User;

final class LeanCloud implements IStorage
{
    private $storage = [];

    public function __construct(
        string $appId,
        string $appKey,
        ?string $appMasterKey = null,
        ?bool $useMasterKey = null,
        ?string $region = null,
        ?string $apiServer = null,
        ?IStorage $storage = null,
        ?string $sessionToken = null,
        ?string $appEnv = null,
        ?string $kernelEnv = null,
        ?bool $debug = null
    ) {
        $appMasterKey = $appMasterKey ?? '';

        Client::initialize($appId, $appKey, $appMasterKey);

        if ($useMasterKey !== null) {
            Client::useMasterKey($useMasterKey);
        }

        if ($region !== null) {
            if (is_numeric($region)) {
                $region = (int) $region;
            }

            Client::useRegion($region);
        }

        if ($apiServer !== null) {
            Client::setServerUrl($apiServer);
        }

        $storage = $storage ?? $this;

        Client::setStorage($storage);

        $useProduction = $appEnv === null ? $kernelEnv === 'prod' : $appEnv === 'production';

        Client::useProduction($useProduction);

        $debug = $debug ?? !$useProduction;

        Client::setDebug($debug);

        if ($sessionToken !== null) {
            Client::getStorage()->set('LC_SessionToken', $sessionToken);
            User::become($sessionToken);
        }
    }

    public function set($key, $val)
    {
        $this->storage[$key] = $val;
    }

    public function get($key)
    {
        return $this->storage[$key] ?? null;
    }

    public function remove($key)
    {
        unset($this->storage[$key]);
    }

    public function clear()
    {
        $this->storage = [];
    }
}