<?php

namespace App\Repository;

abstract class AbstractRepository implements RepositoryInterface
{
    abstract protected function createRecord() : RecordInterface;
}
