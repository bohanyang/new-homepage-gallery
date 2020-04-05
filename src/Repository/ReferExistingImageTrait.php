<?php

namespace App\Repository;

use App\Model\Image;
use App\Model\Record;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;

trait ReferExistingImageTrait
{
    /** @var LoggerInterface */
    private $logger;

    private function referExistingImage(ImagePointerInterface $pointer, Image $image, Record $record)
    {
        if (
            $pointer->getWp() !== $image->wp ||
            $pointer->getCopyright() !== $image->copyright
        ) {
            $this->logger->critical(
                "Got image copyright = '{$image->copyright}' and wp = '{$image->wp}'" .
                "But the existing values are '{$pointer->getCopyright()}' and '{$pointer->getWp()}'"
            );
            throw new UnexpectedValueException('Image does not match the existing one');
        }

        $date = $pointer->getLastAppearedOn();

        if ($date === null) {
            $this->logger->debug('Set last appeared on (which was null)');
            $pointer->setLastAppearedOn($record->date);
        } elseif ($date->get() < $record->date->get()) {
            $this->logger->debug("Set last appeared on (which was {$date->get()->format('Y/n/j')})");
            $pointer->setLastAppearedOn($record->date);
        }
    }
}
