<?php

namespace App\Repository;

use App\Model\Date;
use App\Model\Image;
use App\Model\ImageView;
use App\Model\Record;
use App\Model\RecordView;
use App\Repository\Doctrine\InsertTrait;
use App\Repository\Doctrine\SchemaTrait;
use App\Repository\Doctrine\SelectQuery;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Provider\SchemaProviderInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

use function array_column;
use function Safe\sprintf;

class DoctrineRepository implements RepositoryInterface, SchemaProviderInterface
{
    use ReferExistingImageTrait;
    use InsertTrait;
    use SchemaTrait;

    /** @var Connection */
    private $conn;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(Connection $conn, LoggerInterface $logger)
    {
        //$conn->getConfiguration()->setSQLLogger(null);
        $this->conn = $conn;
        $this->logger = $logger;
        $this->register($conn->getDatabasePlatform());
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
        $type = $this->imageTable->getColumnType('name');

        $query
            ->getBuilder()
            ->from($imageTable, 'i')
            ->where('i.name = ?')
            ->setParameter(0, $name, $type);

        $image = $query->fetchAll();

        return $image === [] ? null : $image[0][$imageTable];
    }

    private function findOrCreateImage(Image $image, Record $record) : string
    {
        $result = $this->findImage($image->name);

        if ($result === null) {
            $image = $image->all();
            //$image['first_appeared_on'] = $record->date;
            //$image['last_appeared_on'] = $record->date;
            $this->insertImage($image);
            return $image['id'];
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
        $marketType = $this->recordTable->getColumnType('market');
        $dateType = $this->recordTable->getColumnType('date_');

        $query
            ->getBuilder()
            ->from($recordTable, 'r')
            ->join('r', $imageTable, 'i', 'r.image_id = i.id')
            ->where('r.market = ?', 'r.date_ = ?')
            ->setMaxResults(1)
            ->setParameters([$market, $date], [$marketType, $dateType]);

        $record = $query->fetchAll();

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
        $type = $this->imageTable->getColumnType('name');

        $query
            ->getBuilder()
            ->from($imageTable, 'i')
            ->leftJoin('i', $recordTable, 'r', 'i.id = r.image_id')
            ->where('i.name = ?')
            ->orderBy('r.date_', 'DESC')
            ->setParameter(0, $name, $type);

        $results = $query->fetchAll();

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

        $images = $query->fetchAll();

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
        $type = $this->recordTable->getColumnType('date_');

        $query
            ->getBuilder()
            ->from($recordTable, 'r')
            ->join('r', $imageTable, 'i', 'r.image_id = i.id')
            ->where('r.date_ = ?')
            ->orderBy('r.market')
            ->setParameter(0, $date, $type);

        $query->execute();
        $images = [];

        while ($query->fetch()) {
            $name = $query->getField($imageTable, 'name');

            if (!isset($images[$name])) {
                $images[$name] = $query->getResult($imageTable);
            }

            $images[$name]['records'][] = new Record($query->getResult($recordTable));
        }

        if ($images === []) {
            throw NotFoundException::date($date);
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

        $results = $query->fetchAll();

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

        $results = $query->fetchAll();

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

        $results = $query->fetchAll();

        return array_column($results, $recordTable);
    }

    public function findMarketsHaveRecordOn(Date $date, array $markets) : array
    {
        $query = new SelectQuery($this->conn);
        $recordTable = $query->addTable($this->recordTable, ['market']);
        $dateType = $this->recordTable->getColumnType('date_');
        [$markets, $marketType] = $this->recordTable->getArrayParam('market', $markets);

        $query
            ->getBuilder()
            ->from($recordTable)
            ->where('date_ = ?', 'market IN (?)')
            ->setParameters([$date, $markets], [$dateType, $marketType]);

        $results = $query->fetchAll();

        foreach ($results as $i => $result) {
            $results[$i] = $result[$recordTable]['market'];
        }

        return $results;
    }
}
