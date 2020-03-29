<?php


namespace App\Design;


interface RepositoryInterface
{
    public function createRecord() : RecordInterface;
}
