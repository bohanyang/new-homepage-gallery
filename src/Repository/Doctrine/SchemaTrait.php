<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

trait SchemaTrait
{
    /** @var ImageTable */
    private $imageTable;

    /** @var RecordTable */
    private $recordTable;

    private function register(AbstractPlatform $platform)
    {
        Type::addType(ObjectIdType::NAME, ObjectIdType::class);
        Type::addType(MarketType::NAME, MarketType::class);
        Type::addType(DateType::NAME, DateType::class);
        Type::addType(SerializedBinaryType::NAME, SerializedBinaryType::class);
        $this->imageTable = new ImageTable($platform);
        $this->recordTable = new RecordTable($platform);
    }

    public function createSchema() : Schema
    {
        $schema = new Schema([], [], $this->conn->getSchemaManager()->createSchemaConfig());
        $this->recordTable->addToSchema($schema);
        $this->imageTable->addToSchema($schema);
        return $schema;
    }
}