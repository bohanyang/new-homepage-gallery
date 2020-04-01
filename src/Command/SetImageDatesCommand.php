<?php

namespace App\Command;

use App\Repository\DoctrineRepository;
use App\Repository\LeanCloud\Image;
use App\Repository\LeanCloudRepository;
use LeanCloud\LeanObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SetImageDatesCommand extends Command
{
    /** @var DoctrineRepository */
    private $doctrine;
    
    /** @var LeanCloudRepository */
    private $leancloud;

    public function __construct(DoctrineRepository $doctrine, LeanCloudRepository $leancloud)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->leancloud = $leancloud;
    }

    protected static $defaultName = 'app:set-image-dates';

    protected function configure()
    {
        $this
            ->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $limit = 1000;
        $skip = 0;
        do {
            $count = 0;
            $results = $this->doctrine->exportImageDates($skip, $limit);
            foreach ($results as $i => $result) {
                /** @var Image $object */
                $object = LeanObject::create(Image::CLASS_NAME, $result['id']);
                $object->setFirstAppearedOn($result['first_appeared_on']);
                $object->setLastAppearedOn($result['last_appeared_on']);
                $results[$i] = $object;
                $count++;
            }
            LeanObject::saveAll($results);
            $skip = $skip + $count;
            $io->note('Finished ' . $skip);
        } while ($count === $limit);
        return 0;
    }
}
