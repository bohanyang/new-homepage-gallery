<?php

namespace App\Command;

use App\Repository\Doctrine\Repository;
use App\Repository\LeanCloudRepository;
use BohanYang\BingWallpaper\CurrentTime;
use BohanYang\BingWallpaper\ArchiveClient;
use BohanYang\BingWallpaper\Market;
use Safe\DateTimeImmutable;
use DateTimeZone;
use LeanCloud\LeanObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_diff;
use function array_keys;
use function array_map;
use function dump;

class FindDupCommand extends Command
{
    /**
     * @var LeanCloudRepository
     */
    private $repository;

    public function __construct(LeanCloudRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    protected static $defaultName = 'app:find-dup';

    protected function configure()
    {
        $this
            ->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $res = $this->repository->findDuplicatedArchive('zh-CN', '20190723', 'BungleBeehive');
        dump(array_map(function (LeanObject $object) {
            return $object->toJSON();
        }, $res));
        return 0;
    }
}
