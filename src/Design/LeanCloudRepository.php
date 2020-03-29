<?php

namespace App\Design;

use App\NormalizedDate;
use LeanCloud\LeanObject;
use LeanCloud\Query;

class LeanCloudRepository implements RepositoryInterface
{
    public const IMAGE_CLASS = 'Image';
    public const RECORD_CLASS = 'Archive';

    public function findImage(string $name) : ?LeanObject
    {
        $query = new Query(self::IMAGE_CLASS);
        $results = $query->equalTo('name', $name)->limit(1)->find();

        return $results === [] ? null : $results[0];
    }

    public function createRecord() : LeanCloudRecord
    {
        return new LeanCloudRecord($this);
    }

    public function createImage(Image $data) : LeanObject
    {
        $image = new LeanObject(self::IMAGE_CLASS);

        foreach ($data as $field => $value) {
            if ($value !== null) {
                $image->set($field, $value);
            }
        }

        return $image;
    }

    private const FIELD_MAPPINGS = [
        'description' => 'info',
        'hotspots' => 'hs',
        'messages' => 'msg',
        'coverstory' => 'cs'
    ];

    private const CALLBACKS = [
        'date' => 'getNormalizedDate'
    ];

    private function getNormalizedDate(NormalizedDate $date)
    {
        return $date->get();
    }

    public function save(Record $data, LeanObject $image)
    {
        $object = new LeanObject(LeanCloudRepository::RECORD_CLASS);
        foreach ($data as $field => $value) {
            if (
                $field !== 'image'
                && $value !== null
            ) {
                $value = isset(self::CALLBACKS[$field]) ? $this->{self::CALLBACKS[$field]}($value) : $value;
                $field = self::FIELD_MAPPINGS[$field] ?? $field;
                $object->set($field, $value);
            }
        }
        $object->set('image', $image);
        LeanObject::saveAll($object);
    }
}
