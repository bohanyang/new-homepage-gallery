<?php

namespace App\Controller;

use App\Repository\NotFoundException;
use App\Repository\RepositoryContract;
use App\Settings;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GalleryController extends AbstractController
{
    public const TZ = 'Asia/Shanghai';
    public const DATE_STRING_FORMAT = 'Ymd';
    public const EXPIRE_TIME = '16:04';
    public const PUBLISH_TIME = '16:05';
    public const DEFAULT_MARKET = 'zh-CN';

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
        try {
            $result = $this->cache->get(
                "image.$name",
                function (ItemInterface $item) use ($name) {
                    $this->expiresAt($item);

                    return $this->repository->getImage($name);
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
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
        try {
            $results = $this->cache->get(
                "browse.$page",
                function (ItemInterface $item) use ($limit, $page) {
                    $this->expiresAt($item);
    
                    return $this->repository->listImages($limit, $page);
                }
            );
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

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

    public function archive(string $market = '', string $date = '')
    {
        if ($market === '') {
            $market = self::DEFAULT_MARKET;
        }

        if ($date === '') {
            $now = new DateTimeImmutable('now', $this->tz);
            $todayPublishTime = new DateTimeImmutable(self::PUBLISH_TIME, $this->tz);
            $isTodayPublished = $now > $todayPublishTime;

            if ($isTodayPublished) {
                $date = $now;
            } else {
                $date = new DateTimeImmutable('yesterday', $this->tz);
            }

            $date = $date->format(self::DATE_STRING_FORMAT);
        }

        $dateInfo = $this->getDateStringInfo($date);

        try {
            $result = $this->cache->get(
                "archive.$market." . $date,
                function (ItemInterface $item) use ($market, $dateInfo) {
                    $this->expiresAt($item);

                    return $this->repository->getArchive($market, $dateInfo['object']);
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        $result['date'] = $dateInfo;
        $image = $result['image'];
        unset($result['image']);

        return $this->render(
            'archive.html.twig',
            [
                'archive' => $result,
                'image' => $image,
                'mirror' => $this->mirror,
                'res' => $this->settings->getImageSize()
            ]
        );
    }

    private function expiresAt(ItemInterface $item)
    {
        $now = new DateTimeImmutable(null, $this->tz);
        $expiresAt = new DateTimeImmutable(self::EXPIRE_TIME, $this->tz);

        if ($expiresAt < $now) {
            $expiresAt = new DateTimeImmutable('tomorrow ' . self::EXPIRE_TIME, $this->tz);
        }

        return $item->expiresAt($expiresAt);
    }

    private function getDateStringInfo(string $dateString)
    {
        $date = DateTimeImmutable::createFromFormat(self::DATE_STRING_FORMAT, $dateString, $this->tz);

        if (!$date instanceof DateTimeImmutable) {
            throw new InvalidArgumentException('Malformed date string');
        }

        $date = $date->modify(self::PUBLISH_TIME);
        $previous = $date->modify('-1 day')->format(self::DATE_STRING_FORMAT);
        $now = new DateTimeImmutable('now', $this->tz);
        $old = (int) $now->diff($date, false)->format('%r%a') < 0;
        $return = [
            'object' => $date,
            'old' => $old,
            'previous' => $previous
        ];

        if ($old) {
            $next = $date->modify('+1 day')->format(self::DATE_STRING_FORMAT);
            $return['next'] = $next;
        }

        return $return;
    }
}
