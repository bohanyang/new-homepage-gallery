<?php

namespace App\Command;

use App\Repository\DoctrineRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportDBCommand extends Command
{
    /** @var DoctrineRepository */
    private $source;

    /** @var DoctrineRepository */
    private $destination;

    /*
    public function __construct(DoctrineRepository $postgres, DoctrineRepository $sqlite)
    {
        parent::__construct();
        $this->source = $postgres;
        $this->destination = $sqlite;
    }
    */

    protected static $defaultName = 'app:import-db';

    protected function configure()
    {
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = 10000;
        $skip = 0;
        do {
            $count = 0;
            $results = $this->source->exportImages($skip, $limit);
            foreach ($results as $result) {
                $this->destination->insertImage($result);
                $count++;
            }
            $skip = $skip + $count;
            $io->note('Finished ' . $skip);
        } while ($count === $limit);
        $io->success('Images imported');
        $skip = 0;
        do {
            $count = 0;
            $results = $this->source->exportRecords($skip, $limit);
            foreach ($results as $result) {
                $this->destination->insertRecord($result);
                $count++;
            }
            $skip = $skip + $count;
            $io->note('Finished ' . $skip);
        } while ($count === $limit);
        $io->success('Archives imported');
        return 0;
    }
}
