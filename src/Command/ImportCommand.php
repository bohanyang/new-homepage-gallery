<?php

namespace App\Command;

use App\Repository\Doctrine\Repository;
use App\Repository\LeanCloudRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommand extends Command
{
    /**
     * @var LeanCloudRepository
     */
    private LeanCloudRepository $repository;
    /**
     * @var Repository
     */
    private Repository $db;

    public function __construct(LeanCloudRepository $repository, Repository $db)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->db = $db;
    }

    protected static $defaultName = 'app:import';

    protected function configure()
    {
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = 1000;
        $skip = 0;
//        do {
//            $count = 0;
//            $results = $this->repository->exportImages($skip, $limit);
//            foreach ($results as $result) {
//                $this->db->insertImage($result);
//                $count++;
//            }
//            $skip = $skip + $count;
//            $io->note('Finished ' . $skip);
//        } while ($count === $limit);
//        $io->success('Images imported');
//        $skip = 0;
        do {
            $count = 0;
            $results = $this->repository->exportArchives($skip, $limit);
            foreach ($results as $result) {
                $this->db->insertArchive($result);
                $count++;
            }
            $skip = $skip + $count;
            $io->note('Finished ' . $skip);
        } while ($count === $limit);
        $io->success('Archives imported');
        return 0;
    }
}
