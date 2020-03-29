<?php

namespace App\Design;

use UnexpectedValueException;

abstract class AbstractRecord implements RecordInterface
{
    /** @var Record */
    protected $data;

    public function setData(Record $data) : void
    {
        $this->data = $data;
        $this->setImagePointer();
    }

    private function setImagePointer() : void
    {
        $image = $this->getImagePointer();

        if ($image === null) {
            $this->createImage();
            return;
        }

        if (
            $image->getWp() !== $this->data->image->wp
            || $image->getCopyright() !== $this->data->image->copyright
        ) {
            throw new UnexpectedValueException('Image does not match the existing one');
        }

        $date = $image->getLastAppearedOn();

        if (
            $date === null
            || $date->get() < $this->data->date->get()
        ) {
            $image->setLastAppearedOn($this->data->date);
        }
    }

    abstract protected function getImagePointer() : ?ImagePointerInterface;

    abstract protected function createImage() : void;
}
