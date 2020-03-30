<?php

namespace App\Controller;

use App\Model\Date;
use App\Model\Image;
use App\Model\ImageView;
use App\Model\RecordView;
use App\Repository\LeanCloudRepository;
use App\Repository\NotFoundException;
use App\Repository\RepositoryInterface;
use App\Settings;
use DateTimeZone;
use League\Uri\UriString;
use Safe\DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GalleryController extends AbstractController
{
    private const TZ = 'Asia/Shanghai';
    private const DATE_STRING_FORMAT = 'Ymd';
    private const EXPIRE_TIME = '16:04';
    private const PUBLISH_TIME = '16:05';
    private const DEFAULT_MARKET = 'zh-CN';
    private const FLAGS = [
        'zh-CN' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe46198a0f5.png',
        'en-US' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe461b2f231.png',
        'en-GB' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe46191e2f3.png',
        'en-AU' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe461988908.png',
        'en-CA' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe461a269d3.png',
        'fr-FR' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe461a8c16a.png',
        'de-DE' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe46198703c.png',
        'pt-BR' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe4619217f5.png',
        'ja-JP' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe461a1faf8.png',
        'fr-CA' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe478326de0.png',
        'en-IN' => 'https://cdn.jsdelivr.net/gh/brentybh/homepage-gallery@5f92ffa5957159a5810d70a646ff7e805a98c4dd/assets/images/5bfe46191fbec.png'
    ];

    /** @var DateTimeZone */
    private $tz;

    /** @var RepositoryInterface */
    private $repository;

    /** @var CacheInterface */
    private $cache;

    /** @var Settings */
    private $settings;

    /** @var array */
    private $params = [];

    public function __construct(
        LeanCloudRepository $repository,
        CacheInterface $cache,
        Settings $settings,
        ContainerBagInterface $params
    ) {
        $this->tz = new DateTimeZone(self::TZ);
        $this->repository = $repository;
        $this->cache = $cache;
        $this->settings = $settings;
        $this->params['image_origin'] = self::getOptionalParam(
            $params,
            'app.image_origin',
            'https://www.bing.com'
        );
        $this->params['video_origin'] = self::getOptionalParam(
            $params,
            'app.video_origin',
            'https://az29176.vo.msecnd.net'
        );
    }

    private static function getOptionalParam(ContainerBagInterface $params, string $key, $default)
    {
        if ($params->has($key)) {
            $value = $params->get($key);
            if ($value !== null) {
                return $value;
            }
        }
        return $default;
    }

    public function image(string $name)
    {
        try {
            /** @var ImageView $image */
            $image = $this->cache->get(
                "image.$name",
                function (ItemInterface $item) use ($name) {
                    $this->expiresAt($item);

                    return $this->repository->getImage($name);
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        return $this->render(
            'image.html.twig',
            [
                'image' => $image,
                'image_origin' => $this->params['image_origin'],
                'image_size' => $this->settings->getImageSize(),
                'video_url' => $this->getVideoUrl($image),
                'flags' => self::FLAGS,
                'date_format' => self::DATE_STRING_FORMAT
            ]
        );
    }

    public function browse(string $page)
    {
        if ($page < 1) {
            throw new BadRequestHttpException("Invalid page number '${page}'");
        }

        $limit = 15;
        $skip = $limit * ($page - 1);

        try {
            /** @var Image[] $images */
            $images = $this->cache->get(
                "browse.$page",
                function (ItemInterface $item) use ($limit, $skip) {
                    $this->expiresAt($item);

                    return $this->repository->listImages($limit, $skip);
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        return $this->render(
            'browse.html.twig',
            [
                'limit' => $limit,
                'page' => $page,
                'images' => $images,
                'image_origin' => $this->params['image_origin'],
                'image_size' => $this->settings->getThumbnailSize()
            ]
        );
    }

    public function date(string $date)
    {
        $date = $this->getDateStringInfo($date);

        try {
            /** @var ImageView[] $images */
            $images = $this->cache->get(
                "date.{$date['current']}",
                function (ItemInterface $item) use ($date) {
                    $this->expiresAt($item);

                    return $this->repository->findImagesByDate(Date::createFromYmd($date['current']));
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        return $this->render(
            'date.html.twig',
            [
                'images' => $images,
                'image_origin' => $this->params['image_origin'],
                'image_size' => $this->settings->getThumbnailSize(),
                'flags' => self::FLAGS,
                'date' => $date
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
            $todayIsPublished = $now > $todayPublishTime;
            $date = $todayIsPublished ? $now : new DateTimeImmutable('yesterday', $this->tz);
            $date = $date->format(self::DATE_STRING_FORMAT);
        }

        $date = $this->getDateStringInfo($date);

        try {
            /** @var RecordView $record */
            $record = $this->cache->get(
                "archive.${market}.{$date['current']}",
                function (ItemInterface $item) use ($market, $date) {
                    $this->expiresAt($item);

                    return $this->repository->getRecord($market, Date::createFromYmd($date['current']));
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());
        }

        return $this->render(
            'archive.html.twig',
            [
                'record' => $record,
                'image' => $record->image,
                'image_origin' => $this->params['image_origin'],
                'video' => $this->getVideoUrl($record->image),
                'image_size' => $this->settings->getImageSize(),
                'date' => $date
            ]
        );
    }

    private function expiresAt(ItemInterface $item)
    {
        $now = new DateTimeImmutable('now', $this->tz);
        $expiresAt = new DateTimeImmutable(self::EXPIRE_TIME, $this->tz);

        if ($expiresAt < $now) {
            $expiresAt = new DateTimeImmutable('tomorrow ' . self::EXPIRE_TIME, $this->tz);
        }

        return $item->expiresAt($expiresAt);
    }

    private function getDateStringInfo(string $dateString)
    {
        $date = DateTimeImmutable::createFromFormat(self::DATE_STRING_FORMAT, $dateString, $this->tz);
        $date = $date->modify(self::PUBLISH_TIME);
        $previous = $date->modify('-1 day')->format(self::DATE_STRING_FORMAT);
        $now = new DateTimeImmutable('now', $this->tz);
        $old = ((int) $now->diff($date, false)->format('%r%a')) < 0;
        $return = [
            'object' => $date,
            'old' => $old,
            'current' => $dateString,
            'previous' => $previous
        ];

        if ($old) {
            $next = $date->modify('+1 day')->format(self::DATE_STRING_FORMAT);
            $return['next'] = $next;
        }

        return $return;
    }

    private function getVideoUrl($image)
    {
        if (empty($image->vid['sources'][1][2])) {
            return null;
        }

        $url = UriString::parse($this->params['video_origin']);
        $url['path'] .= UriString::parse($image->vid['sources'][1][2])['path'];

        return UriString::build($url);
    }
}
