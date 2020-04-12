<?php

namespace App\LeanEngine;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

class ClearCache
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }

    public function __invoke()
    {
        $app = new Application($this->kernel);
        $app->setAutoExit(false);
        $input = new ArrayInput(
            [
                'command' => 'cache:clear',
                '--no-warmup' => true
            ]
        );
        $output = new BufferedOutput();
        $app->run($input, $output);

        return $output->fetch();
    }
}
