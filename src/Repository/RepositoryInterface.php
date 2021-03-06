<?php

namespace App\Repository;

use App\Model\ImageView;
use App\Model\Image;
use App\Model\Date;
use App\Model\Record;
use App\Model\RecordView;

interface RepositoryInterface
{
    public function getImage(string $name) : ImageView;

    /** @return Image[] */
    public function listImages(int $limit, int $skip = 0) : array;

    public function getRecord(string $market, Date $date) : RecordView;

    /** @return ImageView[] */
    public function findImagesByDate(Date $date) : array;

    public function save(Record $record, Image $image) : void;

    /** @return string[] */
    public function findMarketsHaveRecordOn(Date $date, array $markets) : array;
}
