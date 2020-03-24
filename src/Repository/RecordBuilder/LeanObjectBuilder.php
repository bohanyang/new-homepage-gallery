<?php

namespace App\Repository\RecordBuilder;

use App\Repository\LeanCloudRepository;
use LeanCloud\ACL;
use LeanCloud\LeanObject;

class LeanObjectBuilder extends AbstractRecordBuilder
{
    /** @var LeanObject */
    private $object;

    /** @var ACL */
    private $acl;

    public function __construct(LeanCloudRepository $repository)
    {
        $this->repository = $repository;
        $acl = new ACL();
        $acl->setPublicReadAccess(true);
        $acl->setPublicWriteAccess(true);
        $this->acl = $acl;
    }

    protected function createImage()
    {
        $object = new LeanObject(LeanCloudRepository::IMAGE_CLASS_NAME);
        $object->setACL($this->acl);
        $object->set('name', $this->response['image']['name']);
        $object->set('urlbase', $this->response['image']['urlbase']);
        $object->set('copyright', $this->response['image']['copyright']);
        $object->set('wp', $this->response['image']['wp']);
        $object->set('available', false);
        $object->set('firstAppearedOn', $this->date);
        $object->set('lastAppearedOn', $this->date);
        if (isset($this->response['image']['vid'])) {
            $object->set('vid', $this->response['image']['vid']);
        }
        return $object;
    }

    protected function setArchive() : void
    {
        $object = new LeanObject(LeanCloudRepository::ARCHIVE_CLASS_NAME);
        $object->setACL($this->acl);
        $object->set('market', $this->response['market']);
        $object->set('date', $this->date->format('Ymd'));
        $object->set('info', $this->response['description']);
        if (isset($this->response['link'])) {
            $object->set('link', $this->response['link']);
        }
        if (isset($this->response['hotspots'])) {
            $object->set('hs', $this->response['hotspots']);
        }
        if (isset($this->response['messages'])) {
            $object->set('msg', $this->response['messages']);
        }
        $this->object = $object;
    }

    protected function setImagePointer() : void
    {
        $this->object->set('image', $this->getImagePointer());
    }
}