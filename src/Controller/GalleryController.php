<?php

namespace App\Controller;

use App\Repository\RepositoryContract;
use App\Settings;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GalleryController extends AbstractController
{
    /** @var RepositoryContract */
    private $repository;

    /** @var CacheInterface */
    private $cache;

    private const TZ = 'Asia/Shanghai';

    /** @var Settings */
    private $settings;

    public function __construct(RepositoryContract $repository, CacheInterface $cache, Settings $settings)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->settings = $settings;
    }

    public function image(string $name)
    {
        $result = $this->cache->get("image.$name", function (ItemInterface $item) use ($name) {
            $tz = new DateTimeZone(self::TZ);
            $now = new DateTimeImmutable(null, $tz);
            $expiresAt = new DateTimeImmutable('16:04', $tz);

            if ($expiresAt < $now) {
                $expiresAt = new DateTimeImmutable('tomorrow 16:04', $tz);
            }

            $item->expiresAt($expiresAt);

            return $this->repository->getImage($name);
        });

        if (!$result) {
            throw $this->createNotFoundException();
        }

        return $this->render('image.html.twig', [
            'image' => $result,
            'mirror' => 'https://img.penbeat.cn',
            'res' => $this->settings->getImageSize()
        ]);
    }
}
