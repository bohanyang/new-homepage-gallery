<?php

namespace App\Command;

use App\Repository\DoctrineRepository;
use Exception;
use MongoDB\BSON\ObjectId;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class ModelTestCommand extends Command
{

    /** @var DoctrineRepository */
    private $repository;

    public function __construct(DoctrineRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected static $defaultName = 'app:model';

    protected function configure()
    {
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $conn = $this->repository->getConnection();
        $conn->beginTransaction();
        try {
            $this->repository->insertImage(
                [
                    'id' => (new ObjectId())->__toString(),
                    'name' => 'TestImage',
                    'copyright' => 'CopyRightTestImage',
                    'urlbase' => 'UrlTestImage',
                    'wp' => true
                ]
            );
            if (!$io->confirm('Sure?', true)) {
                //throw new RuntimeException('Aborted');
                exit;
            }
        } catch (Throwable $e) {
            $conn->rollBack();

            throw $e;
        }
        $conn->commit();
        return 0;
    }
}
