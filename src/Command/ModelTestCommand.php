<?php

namespace App\Command;

use App\Repository\LeanCloudRepository;
use BohanYang\BingWallpaper\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function dump;

class ModelTestCommand extends Command
{
    /** @var Client */
    private $client;

    /** @var LeanCloudRepository */
    private $repository;

    public function __construct(Client $client, LeanCloudRepository $repository)
    {
        parent::__construct();
        $this->client = $client;
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
        $model = $this->repository->getImage('LiRiverGuilinVideo');
        dump($model);
        return 0;
    }
}
