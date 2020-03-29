<?php

namespace App\Design;

use App\Repository\Doctrine\ArchiveTable;
use App\Repository\Doctrine\ImageTable;
use App\Repository\Doctrine\SelectQuery;
use App\Repository\Doctrine\Serializer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class DoctrineRepository implements RepositoryInterface
{
    /** @var ImageTable */
    private $imageTable;

    /** @var ArchiveTable */
    private $archiveTable;

    /** @var Connection */
    private $conn;

    /** @var AbstractPlatform */
    private $platform;

    public function __construct(Connection $conn)
    {
        //$conn->getConfiguration()->setSQLLogger(null);
        $this->conn = $conn;
        $this->platform = $conn->getDatabasePlatform();
        $serializer = new Serializer();
        $this->imageTable = new ImageTable($this->platform, $serializer);
        $this->archiveTable = new ArchiveTable($this->platform, $serializer);
    }

    public function createRecord() : DoctrineRecord
    {
        return new DoctrineRecord($this);
    }

    public function findImage(string $name)
    {
        $query = new SelectQuery($this->conn);
        $imageTable = $query->addTable($this->imageTable, ['id', 'name', 'copyright', 'wp']);
        [$name, $type] = $this->imageTable->getQueryParam('name', $name);
        $query
            ->getBuilder()
            ->from($imageTable)
            ->where('name = ?')
            ->setMaxResults(1)
            ->setParameter(0, $name, $type);
        $results = $query->getResults();

        return $results === [] ? null : $results[0][$imageTable];
    }

    public function createImage(Image $data) : string
    {
        return $this->conn->lastInsertId();
    }

    public function updateLastAppearedOn(string $id, DateTimeImmutable $date)
    {

    }
}
