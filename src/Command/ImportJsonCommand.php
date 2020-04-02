<?php

namespace App\Command;

use App\Replicator\LeanCloudDoctrineReplicator;
use App\Repository\LeanCloud\Image;
use App\Repository\LeanCloud\Record;
use LeanCloud\LeanObject;
use pcrov\JsonReader\JsonReader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class ImportJsonCommand extends Command
{
    /** @var LeanCloudDoctrineReplicator */
    private $replicator;

    public function __construct(LeanCloudDoctrineReplicator $replicator)
    {
        parent::__construct();
        $this->replicator = $replicator;
    }

    protected static $defaultName = 'app:import-json';

    protected function configure()
    {
        $this
            ->setDescription('Import to database from LeanCloud JSON')
            ->addOption('image', 'i', InputOption::VALUE_REQUIRED, 'Images JSON')
            ->addOption('record', 'r', InputOption::VALUE_REQUIRED, 'Records JSON');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->replicator->getDoctrine()->getConnection();
        $conn->beginTransaction();

        try {
            $this->readData($input->getOption('image'), [$this, 'importImage']);
            $this->readData($input->getOption('record'), [$this, 'importRecord']);
        } catch (Throwable $e) {
            $conn->rollBack();

            throw $e;
        }

        $conn->commit();

        return 0;
    }

    private function importImage(array $data)
    {
        /** @var Image $object */
        $object = LeanObject::create(Image::CLASS_NAME);
        $object->mergeAfterSave($data);
        $this->replicator->importImage($object);
    }

    private function importRecord(array $data)
    {
        /** @var Record $object */
        $object = LeanObject::create(Record::CLASS_NAME);
        $object->mergeAfterSave($data);
        $this->replicator->importRecord($object);
    }

    private function readData(string $json, callable $callback)
    {
        $reader = new JsonReader();
        $reader->open($json);
        $reader->read('results');
        $reader->read();
        while ($reader->type() === JsonReader::OBJECT) {
            $callback($reader->value());
            $reader->next();
        }
        $reader->close();
    }
}
