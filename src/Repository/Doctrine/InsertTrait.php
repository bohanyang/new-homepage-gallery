<?php

namespace App\Repository\Doctrine;

use Doctrine\DBAL\Connection;
use RuntimeException;

trait InsertTrait
{
    /** @var ImageTable */
    private $imageTable;

    /** @var RecordTable */
    private $recordTable;

    /** @var Connection */
    private $conn;

    public function insertImage($image) : void
    {
        $this->insert($this->imageTable, $image);
    }

    public function insertRecord($record) : void
    {
        $this->insert($this->recordTable, $record);
    }

    private function insert(AbstractTable $table, $data) : void
    {
        [$params, $types, $columns, $placeholders] = $table->getInsertParams($data);

        $sql = "INSERT INTO {$table->getName()} (${columns}) VALUES (${placeholders})";

        $inserted = $this->conn->executeUpdate($sql, $params, $types);

        if ($inserted !== 1) {
            throw new RuntimeException("Failed to insert into table {$table->getName()}");
        }
    }
}