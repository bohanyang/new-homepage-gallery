<?php

namespace App\Repository;

use App\Model\Image;
use App\Model\Record;
use UnexpectedValueException;

trait RepositoryTrait
{
    private function referExistingImage(ImagePointerInterface $pointer, Image $image, Record $record)
    {
        if (
            $pointer->getWp() !== $image->wp ||
            $pointer->getCopyright() !== $image->copyright
        ) {
            throw new UnexpectedValueException('Image does not match the existing one');
        }
        $date = $pointer->getLastAppearedOn();
        if (
            $date === null ||
            $date->get() < $record->date->get()
        ) {
            $pointer->setLastAppearedOn($record->date);
        }
    }
}
