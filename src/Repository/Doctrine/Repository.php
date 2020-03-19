<?php

namespace App\Repository\Doctrine;

use App\Repository\NotFoundException;
use App\Repository\RepositoryContract;
use App\Repository\RepositoryTrait;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function Safe\sprintf as sprintf;

class Repository implements SchemaProviderInterface, RepositoryContract
{
    use RepositoryTrait;

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
        $this->conn = $conn;
        $this->platform = $conn->getDatabasePlatform();
        $serializer = new Serializer();
        $this->imageTable = new ImageTable($this->platform, $serializer);
        $this->archiveTable = new ArchiveTable($this->platform, $serializer);
    }

    public function createSchema() : Schema
    {
        $schema = new Schema([], [], $this->conn->getSchemaManager()->createSchemaConfig());
        $this->archiveTable->addToSchema($schema);
        $this->imageTable->addToSchema($schema);
        return $schema;
    }

    public function insertImage($image)
    {
        return $this->insert($this->imageTable, $image);
    }

    public function insertArchive($archive)
    {
        return $this->insert($this->archiveTable, $archive);
    }

    private function insert(AbstractTable $table, $data) : string
    {
        [$params, $types, $columns, $placeholders] = $table->getInsertParams($data);

        $sql = "INSERT INTO {$table->getName()} (${columns}) VALUES (${placeholders})";

        $inserted = $this->conn->executeUpdate($sql, $params, $types);

        if ($inserted !== 1) {
            throw new RuntimeException("Failed to insert into table {$table->getName()}");
        }

        return $this->conn->lastInsertId();
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
        if ($results === []) {
            return null;
        }
        return $results[0][$imageTable];
    }

    public function saveArchive($archive)
    {
        [$params, $types, $columns, $placeholders] =
            $this->archiveTable->getSubQueryInsertParams($archive, ['market', 'date_', 'image_id']);

        $placeholders = $this->platform->getDummySelectSQL($placeholders);

        $subQuery = $this->conn
            ->createQueryBuilder()
            ->select('id')
            ->from($this->archiveTable->getName())
            ->where('market = ?', 'date_ = ? OR image_id = ?')
            ->getSQL();

        $sql = "INSERT INTO {$this->archiveTable->getName()} (${columns}) ${placeholders} WHERE NOT EXISTS (${subQuery})";

        $inserted = $this->conn->executeUpdate($sql, $params, $types);

        if ($inserted !== 1) {
            throw new RuntimeException("An archive has the same date or image id already exists");
        }

        return $this->conn->lastInsertId();
    }

    private function getImageIdOrInsert($image) : string
    {
        $result = $this->findImage($image['name']);

        if ($result === null) {
            return $this->insertImage($image);
        } else {
            if (
                $image['copyright'] !== $result['copyright'] ||
                $image['wp'] !== $result['wp']
            ) {
                throw new UnexpectedValueException('Image does not match the existing one');
            }

            return $result['id'];
        }
    }

    public function save(array $data)
    {
        $imageName = $data['image']['name'];
        $this->conn->beginTransaction();

        try {
            $data['image_id'] = $this->getImageIdOrInsert($data['image']);
            unset($data['image']);
            $id = $this->saveArchive($data);
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw new RuntimeException(
                sprintf(
                    'Failed to save result of market "%s" on "%s" with image "%s"',
                    $data['market'],
                    $data['date']->format('Y-m-d'),
                    $imageName
                ), 0, $e
            );
        }

        $this->conn->commit();

        return $id;
    }

    public function getArchive(string $market, DateTimeImmutable $date)
    {
        $query = new SelectQuery($this->conn);
        $archiveTable = $query->addTable($this->archiveTable, $this->archiveTable->getAllColumns(), 'a');
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns(), 'i');
        [$market, $marketType] = $this->archiveTable->getQueryParam('market', $market);
        [$date, $dateType] = $this->archiveTable->getQueryParam('date_', $date);
        $query
            ->getBuilder()
            ->from($archiveTable, 'a')
            ->join('a', $imageTable, 'i', 'a.image_id = i.id')
            ->where('a.market = ?', 'a.date_ = ?')
            ->setMaxResults(1)
            ->setParameters([$market, $date], [$marketType, $dateType]);
        $results = $query->getData();
        if ($results === []) {
            throw new NotFoundException('Archive not found');
        }
        $archive = $results[0][$archiveTable];
        $archive['image'] = $results[0][$imageTable];
        return $archive;
    }

    public function getImage(string $name)
    {
        $query = new SelectQuery($this->conn);
        $archiveTable = $query->addTable($this->archiveTable, $this->archiveTable->getAllColumns(), 'a');
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns(), 'i');
        [$name, $type] = $this->imageTable->getQueryParam('name', $name);
        $query
            ->getBuilder()
            ->from($imageTable, 'i')
            ->leftJoin('i', $archiveTable, 'a', 'i.id = a.image_id')
            ->where('i.name = ?')
            ->orderBy('a.date_', 'DESC')
            ->setParameter(0, $name, $type);
        $results = $query->getData();
        if ($results === []) {
            throw new NotFoundException('Image not found');
        }
        $image = $results[0][$imageTable];
        $image['archives'] = array_column($results, $archiveTable);
        return $image;
    }

    public function listImages(int $limit, int $page)
    {
        $skip = $this->getSkip($limit, $page);
        $query = new SelectQuery($this->conn);
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns());
        $query
            ->getBuilder()
            ->from($imageTable)
            ->orderBy('id', 'DESC')
            ->setFirstResult($skip)
            ->setMaxResults($limit);
        $results = $query->getData();
        if ($results === []) {
            throw new NotFoundException('No images found');
        }

        return array_column($results, $imageTable);
    }

    public function findArchivesByDate(DateTimeImmutable $date)
    {
        $query = new SelectQuery($this->conn);
        $archiveTable = $query->addTable($this->archiveTable, $this->archiveTable->getAllColumns(), 'a');
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns(), 'i');
        [$date, $type] = $this->archiveTable->getQueryParam('date_', $date);
        $query
            ->getBuilder()
            ->from($archiveTable, 'a')
            ->join('a', $imageTable, 'i', 'a.image_id = i.id')
            ->where('a.date_ = ?')
            ->orderBy('a.market')
            ->setParameter(0, $date, $type);
        $results = $query->getData();
        if ($results === []) {
            throw new NotFoundException('No archives found');
        }
        $images = [];

        foreach ($results as $i => $result) {
            $imageId = $result[$imageTable]['id'];

            if (!isset($images[$imageId])) {
                $images[$imageId] = $result[$imageTable];
            }

            $images[$imageId]['archives'][] = $result[$archiveTable];
        }

        return $images;
    }
}
