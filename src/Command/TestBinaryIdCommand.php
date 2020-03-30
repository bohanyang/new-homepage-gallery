<?php

namespace App\Command;

use App\Repository\DoctrineRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class TestBinaryIdCommand extends Command
{
    /**
     * @var DoctrineRepository
     */
    private DoctrineRepository $repository;

    public function __construct(DoctrineRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected static $defaultName = 'app:test-bin-id';

    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $id = $this->repository->insertImage([
            'id' => '5e75a5dc3314d21dc302d78c',
            'name' => 'TestImage',
            'copyright' => 'CopyRightTestImage',
            'urlbase' => 'UrlTestImage',
            'wp' => true
                                       ]);
        dump($id);
        return 0;
    }
}
