<?php

namespace App\Command;

use App\Model\Date;
use App\Repository\DoctrineRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function dump;

class EmptyCommand extends Command
{
    /**
     * @var DoctrineRepository
     */
    private $repository;

    public function __construct(DoctrineRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected static $defaultName = 'app:cmd';

    protected function configure()
    {
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->repository->findImagesByDate(Date::createFromYmd('20150901'));

        return 0;
    }
}
