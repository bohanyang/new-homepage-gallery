<?php

namespace App\Command;

use App\Model\Date;
use App\Model\Image;
use App\Model\Record;
use App\Repository\LeanCloudRepository;
use BohanYang\BingWallpaper\CurrentTime;
use BohanYang\BingWallpaper\Client;
use BohanYang\BingWallpaper\RequestParams;
use BohanYang\BingWallpaper\Market;
use DateTimeImmutable;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function array_diff;
use function array_keys;

class TestCommand extends Command
{
    /**
     * @var LeanCloudRepository
     */
    private $repository;
    /**
     * @var Client
     */
    private Client $client;

    public function __construct(LeanCloudRepository $repository, Client $client)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->client = $client;
    }

    protected static $defaultName = 'app:test';

    protected function configure()
    {
        $this
            ->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $io = new SymfonyStyle($input, $output);
        $markets = [];
        foreach (
            [
                'en-US',
                'pt-BR',
                'en-CA',
                'fr-CA',
                'en-GB',
                'fr-FR',
                'de-DE',
                'en-IN',
                'zh-CN',
                'ja-JP',
                'en-AU',
            ] as $market
        ) {
            $markets[$market] = new Market($market);
        }
        $now = new CurrentTime();
        $markets = $now->getMarketsHaveBecomeTheLaterDate($markets);
        $req = array_keys($markets);
        $res = $this->repository->findMarketsHaveArchiveOfDate($now->getTheLaterDate(), $req);
        dump($res);
        $res = array_diff($req, $res);
        dump($res);
        $marketDates = [];
        foreach ($res as $market) {
            $marketDates[] = RequestParams::create($markets[$market], $now->getTheLaterDate());
        }
        $res = $this->client->batch($marketDates);
        foreach ($res as $record) {
            $image = new Image($record['image']);
            unset($record['image']);
            $record['date'] = Date::createFromLocal($record['date']);
            $record = new Record($record);
            $this->repository->save($record, $image);
        }
        return 0;
    }
}
