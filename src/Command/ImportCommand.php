<?php

namespace App\Command;

use App\Replicator\LeanCloudDoctrineReplicator;
use App\Repository\LeanCloud\Image;
use App\Repository\LeanCloud\Record;
use LeanCloud\LeanObject;
use LeanCloud\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportCommand extends Command
{

    /** @var LeanCloudDoctrineReplicator */
    private $replicator;

    /*
    public function __construct(LeanCloudDoctrineReplicator $replicator)
    {
        parent::__construct();
        $this->replicator = $replicator;
    }
    */

    protected static $defaultName = 'app:import';

    protected function configure()
    {
        $this->setDescription('');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $images = ['5e82ef2791db28006a757af9'];
        $count = 0;
        foreach ($images as $image) {
            $image = (new Query(Image::CLASS_NAME))->get($image);
            /** @var Image $image */
            $this->replicator->importImage($image);
            $count++;
        }
        $io->note("Finished Image ${count}");
        $records = (new Query(Record::CLASS_NAME))->equalTo('date', '20200331')->find();
        $count = 0;
        foreach ($records as $record) {
            $this->replicator->importRecord($record);
            $count++;
        }
        $io->note("Finished Record ${count}");
        return 0;
    }
}
