<?php

namespace App\Repository;

use App\Model\ImageView;
use App\Model\Image;
use App\Model\Date;
use App\Model\RecordView;

interface RepositoryInterface
{
    public function getImage(string $name) : ImageView;

    /** @return Image[] */
    public function listImages(int $limit, int $skip = 0) : array;

    public function getRecord(string $market, Date $date) : RecordView;

    /** @return ImageView[] */
    public function findImagesByDate(Date $date) : array;
}
