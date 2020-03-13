<?php

namespace App\Controller;

use OTPHP\TOTPInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Cache\PruneableInterface;
use Symfony\Contracts\Cache\CacheInterface;

final class CachePruningController extends AbstractController
{
    /** @var CacheInterface */
    private $cache;

    /** @var TOTPInterface */
    private $TOTP;

    /** @var LoggerInterface */
    private $logger;

    public function __construct(CacheInterface $cache, TOTPInterface $TOTP, LoggerInterface $logger)
    {
        $this->cache = $cache;
        $this->TOTP = $TOTP;
        $this->logger = $logger;
    }

    public function index(string $token)
    {
        if ($this->cache instanceof PruneableInterface && $this->TOTP->verify($token) && $this->cache->prune()) {
            $this->logger->notice('Cache pruned');
        }

        return $this->redirectToRoute('homepage');
    }
}
