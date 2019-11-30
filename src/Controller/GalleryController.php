<?php

namespace App\Controller;

use App\Repository\RepositoryContract;
use App\Settings;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GalleryController extends AbstractController
{
    public const TZ = 'Asia/Shanghai';

    /** @var RepositoryContract */
    private $repository;

    /** @var CacheInterface */
    private $cache;

    /** @var Settings */
    private $settings;

    /** @var DateTimeZone */
    private $tz;

    private $mirror = 'https://img.penbeat.cn';

    public function __construct(RepositoryContract $repository, CacheInterface $cache, Settings $settings)
    {
        $this->repository = $repository;
        $this->cache = $cache;
        $this->settings = $settings;
        $this->tz = new DateTimeZone(self::TZ);
    }

    public function image(string $name)
    {
        $result = $this->cache->get(
            "image.$name",
            function (ItemInterface $item) use ($name) {
                $this->expiresAt($item);

                return $this->repository->getImage($name);
            }
        );

        if (!$result) {
            throw $this->createNotFoundException();
        }

        return $this->render(
            'image.html.twig',
            [
                'image' => $result,
                'mirror' => $this->mirror,
                'res' => $this->settings->getImageSize()
            ]
        );
    }

    public function browse(int $page)
    {
        $limit = 15;
        $results = $this->cache->get(
            "browse.$page",
            function (ItemInterface $item) use ($limit, $page) {
                $this->expiresAt($item);

                return $this->repository->listImages($limit, $page);
            }
        );

        return $this->render(
            'browse.html.twig',
            [
                'limit' => $limit,
                'page' => $page,
                'images' => $results,
                'mirror' => $this->mirror,
                'res' => $this->settings->getThumbnailSize()
            ]
        );
    }

    private function expiresAt(ItemInterface $item) {
        $now = new DateTimeImmutable(null, $this->tz);
        $expiresAt = new DateTimeImmutable('16:04', $this->tz);

        if ($expiresAt < $now) {
            $expiresAt = new DateTimeImmutable('tomorrow 16:04', $this->tz);
        }

        return $item->expiresAt($expiresAt);
    }
}
