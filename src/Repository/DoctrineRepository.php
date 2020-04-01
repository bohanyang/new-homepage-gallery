<?php

namespace App\Repository;

use App\Model\Date;
use App\Model\Image;
use App\Model\ImageView;
use App\Model\Record;
use App\Model\RecordView;
use App\Repository\Doctrine\AbstractTable;
use App\Repository\Doctrine\RecordTable;
use App\Repository\Doctrine\ImageTable;
use App\Repository\Doctrine\SelectQuery;
use App\Repository\Doctrine\Serializer;
use DateTimeImmutable;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use MongoDB\BSON\ObjectId;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;
use UnexpectedValueException;

use function array_column;
use function Safe\sprintf;

class DoctrineRepository implements RepositoryInterface, SchemaProviderInterface
{
    use RepositoryTrait;

    /** @var ImageTable */
    private $imageTable;

    /** @var RecordTable */
    private $recordTable;

    /** @var Connection */
    private $conn;

    /** @var AbstractPlatform */
    private $platform;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $conn, LoggerInterface $logger)
    {
        //$conn->getConfiguration()->setSQLLogger(null);
        $this->conn = $conn;
        $this->platform = $conn->getDatabasePlatform();
        $serializer = new Serializer();
        $this->imageTable = new ImageTable($this->platform, $serializer);
        $this->recordTable = new RecordTable($this->platform, $serializer);
        $this->logger = $logger;
    }

    public function createSchema() : Schema
    {
        $schema = new Schema([], [], $this->conn->getSchemaManager()->createSchemaConfig());
        $this->recordTable->addToSchema($schema);
        $this->imageTable->addToSchema($schema);
        return $schema;
    }

    public function getConnection() : Connection
    {
        return $this->conn;
    }

    public function insertImage($image) : string
    {
        $this->insert($this->imageTable, $image);
        return $image['id'];
    }

    public function insertRecord($record) : string
    {
        $this->insert($this->recordTable, $record);
        return $record['id'];
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

    public function saveRecord(array $record) : void
    {
        [$params, $types, $columns, $placeholders] =
            $this->recordTable->getSubQueryInsertParams($record, ['market', 'date_', 'image_id']);

        $subQuery = $this->conn
            ->createQueryBuilder()
            ->select('id')
            ->from($this->recordTable->getName())
            ->where('market = ?', 'date_ = ? OR image_id = ?')
            ->getSQL();

        $sql = "INSERT INTO {$this->recordTable->getName()} (${columns}) ${placeholders} WHERE NOT EXISTS (${subQuery})";

        $inserted = $this->conn->executeUpdate($sql, $params, $types);

        if ($inserted !== 1) {
            throw new RuntimeException("An archive has the same date or image id already exists");
        }
    }

    private function findImage(string $name) : ?array
    {
        $query = new SelectQuery($this->conn);
        $imageTable = $query->addTable($this->imageTable, ['id', 'wp', 'copyright', 'last_appeared_on'], 'i');
        [$name, $type] = $this->imageTable->getQueryParam('name', $name);

        $query
            ->getBuilder()
            ->from($imageTable, 'i')
            ->where('i.name = ?')
            ->setParameter(0, $name, $type);

        $image = $query->getResults();

        return $image === [] ? null : $image[0][$imageTable];
    }

    private function findOrCreateImage(Image $image, Record $record) : string
    {
        $result = $this->findImage($image->name);

        if ($result === null) {
            $image = $image->all();
            //$image['first_appeared_on'] = $record->date;
            //$image['last_appeared_on'] = $record->date;
            return $this->insertImage($image);
        }

        $pointer = new DoctrineImagePointer($this, $result);
        $this->referExistingImage($pointer, $image, $record);

        return $result['id'];
    }

    public function save(Record $record, Image $image) : void
    {
        $this->conn->beginTransaction();

        try {
            $imageId = $this->findOrCreateImage($image, $record);
            $data = $record->all();
            $data['image_id'] = $imageId;
            $this->saveRecord($data);
        } catch (Throwable $e) {
            $this->conn->rollBack();
            throw new RuntimeException(
                sprintf(
                    'Failed to save result of market "%s" on "%s" with image "%s"',
                    $record->market,
                    $record->date->get()->format('Y/n/j'),
                    $image->name
                ), 0, $e
            );
        }

        $this->conn->commit();
    }

    public function getRecord(string $market, Date $date) : RecordView
    {
        $query = new SelectQuery($this->conn);
        $recordTable = $query->addTable($this->recordTable, $this->recordTable->getAllColumns(), 'r');
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns(), 'i');
        [$market, $marketType] = $this->recordTable->getQueryParam('market', $market);
        [$date, $dateType] = $this->recordTable->getQueryParam('date_', $date);

        $query
            ->getBuilder()
            ->from($recordTable, 'r')
            ->join('r', $imageTable, 'i', 'r.image_id = i.id')
            ->where('r.market = ?', 'r.date_ = ?')
            ->setMaxResults(1)
            ->setParameters([$market, $date], [$marketType, $dateType]);

        $record = $query->getData();

        if ($record === []) {
            throw NotFoundException::record($market, $date);
        }

        $image = new Image($record[0][$imageTable]);
        $record = $record[0][$recordTable];
        $record['image'] = $image;

        return new RecordView($record);
    }

    public function getImage(string $name) : ImageView
    {
        $query = new SelectQuery($this->conn);
        $recordTable = $query->addTable($this->recordTable, $this->recordTable->getAllColumns(), 'r');
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns(), 'i');
        [$name, $type] = $this->imageTable->getQueryParam('name', $name);

        $query
            ->getBuilder()
            ->from($imageTable, 'i')
            ->leftJoin('i', $recordTable, 'r', 'i.id = r.image_id')
            ->where('i.name = ?')
            ->orderBy('r.date_', 'DESC')
            ->setParameter(0, $name, $type);

        $results = $query->getData();

        if ($results === []) {
            throw NotFoundException::image($name);
        }

        $image = $results[0][$imageTable];
        $image['records'] = array_column($results, $recordTable);

        return new ImageView($image);
    }

    public function listImages(int $limit, int $skip = 0) : array
    {
        $query = new SelectQuery($this->conn);
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns());

        $query
            ->getBuilder()
            ->from($imageTable)
            ->orderBy('id', 'DESC')
            ->setFirstResult($skip)
            ->setMaxResults($limit);

        $images = $query->getData();

        if ($images === []) {
            throw NotFoundException::images();
        }

        foreach ($images as $i => $result) {
            $images[$i] = new Image($result[$imageTable]);
        }

        return $images;
    }

    public function findImagesByDate(Date $date) : array
    {
        $query = new SelectQuery($this->conn);
        $recordTable = $query->addTable($this->recordTable, $this->recordTable->getAllColumns(), 'r');
        $imageTable = $query->addTable($this->imageTable, $this->imageTable->getAllColumns(), 'i');
        [$date, $type] = $this->recordTable->getQueryParam('date_', $date);

        $query
            ->getBuilder()
            ->from($recordTable, 'r')
            ->join('r', $imageTable, 'i', 'r.image_id = i.id')
            ->where('r.date_ = ?')
            ->orderBy('r.market')
            ->setParameter(0, $date, $type);

        $results = $query->getData();

        if ($results === []) {
            throw NotFoundException::date($date);
        }

        $images = [];

        foreach ($results as $i => $result) {
            $image = $result[$imageTable];
            $name = $image['name'];

            if (!isset($images[$name])) {
                $images[$name] = $image;
            }

            $images[$name]['records'][] = new Record($result[$recordTable]);
        }

        foreach ($images as $name => $image) {
            $images[] = new ImageView($image);
            unset($images[$name]);
        }

        return $images;
    }

    public function exportImages(int $skip, int $limit)
    {
        $query = new SelectQuery($this->conn);
        $imageTable = $query->addTable($this->imageTable, ['id', 'first_appeared_on', 'last_appeared_on', 'name', 'urlbase', 'copyright', 'wp', 'vid']);

        $query
            ->getBuilder()
            ->from($imageTable)
            ->setFirstResult($skip)
            ->setMaxResults($limit);

        $results = $query->getResults();

        return array_column($results, $imageTable);
    }

    public function exportImageDates(int $skip, int $limit)
    {
        $query = new SelectQuery($this->conn);
        $imageTable = $query->addTable($this->imageTable, ['id', 'last_appeared_on', 'first_appeared_on']);

        $query
            ->getBuilder()
            ->from($imageTable)
            ->setFirstResult($skip)
            ->setMaxResults($limit);

        $results = $query->getResults();

        return array_column($results, $imageTable);
    }

    public function exportRecords(int $skip, int $limit)
    {
        $query = new SelectQuery($this->conn);
        $recordTable = $query->addTable($this->recordTable, ['id', 'image_id', 'market', 'date_', 'description', 'link', 'hotspots', 'messages', 'coverstory']);

        $query
            ->getBuilder()
            ->from($recordTable)
            ->setFirstResult($skip)
            ->setMaxResults($limit);

        $results = $query->getData();

        return array_column($results, $recordTable);
    }

    public function findMarketsHaveRecordOn(Date $date, array $markets) : array
    {
        $query = new SelectQuery($this->conn);
        $recordTable = $query->addTable($this->recordTable, ['market']);
        [$date, $dateType] = $this->recordTable->getQueryParam('date_', $date);
        [$markets, $marketType] = $this->recordTable->getArrayParam('market', $markets);

        $query
            ->getBuilder()
            ->from($recordTable)
            ->where('date_ = ?', 'market IN (?)')
            ->setParameters([$date, $markets], [$dateType, $marketType]);

        $results = $query->getResults();

        foreach ($results as $i => $result) {
            $results[$i] = $result[$recordTable]['market'];
        }

        return $results;
    }
}
