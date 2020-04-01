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
use BohanYang\BingWallpaper\Market;
use DateTimeZone;
use League\Uri\UriString;
use Safe\DateTime;
use Safe\DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class GalleryController extends AbstractController
{
    private const DATE_STRING_FORMAT = 'Ymd';
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

    /** @var RepositoryInterface */
    private $repository;

    /** @var CacheInterface */
    private $cache;

    /** @var Settings */
    private $settings;

    /** @var array */
    private $params = [];

    public function __construct(
        RepositoryInterface $repository,
        CacheInterface $cache,
        Settings $settings,
        ContainerBagInterface $params
    ) {
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
                    $this->expireNextHour($item);

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

        if ($page < 1) {
            throw $this->createNotFoundException();
        }

        try {
            /** @var Image[] $images */
            $images = $this->cache->get(
                "browse.$page",
                function (ItemInterface $item) use ($limit, $skip) {
                    $this->expireNextHour($item);

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
        $date = $this->getDateStringInfo($date, new DateTimeZone('Australia/Sydney'));

        try {
            /** @var ImageView[] $images */
            $images = $this->cache->get(
                "date.{$date['current']}",
                function (ItemInterface $item) use ($date) {
                    $this->expireNextHour($item);

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

        $market = new Market($market);
        $timezone = $market->getTimeZone();

        if ($date === '') {
            $date = $this->getTodayInfo($timezone);
        } else {
            $date = $this->getDateStringInfo($date, $timezone);
        }

        try {
            /** @var RecordView $record */
            $record = $this->cache->get(
                "archive.${market}.{$date['current']}",
                function (ItemInterface $item) use ($market, $date) {
                    $this->expireTomorrow($item, $market->getTimeZone());

                    return $this->repository->getRecord($market->getName(), Date::createFromYmd($date['current']));
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

    private function expireNextHour(ItemInterface $item)
    {
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
        $delay = $now->setTime($now->format('G'), 3, 1);

        if ($now < $delay) {
            return $item->expiresAt($delay);
        }

        return $item->expiresAt($delay->modify('+1 hour'));
    }

    private function expireTomorrow(ItemInterface $item, DateTimeZone $timezone)
    {
        $now = new DateTimeImmutable('now', $timezone);
        $delay = $now->setTime(0, 3, 1);

        if ($now < $delay) {
            return $item->expiresAt($delay);
        }

        return $item->expiresAt($delay->modify('+1 day'));
    }

    private function getTodayInfo(DateTimeZone $timezone)
    {
        $now = new DateTimeImmutable('now', $timezone);
        $delay = $now->setTime(0, 3);

        if ($now < $delay) {
            $now = $now->modify('yesterday');
        } else {
            $now = $now->setTime(0, 0);
        }

        return [
            'object' => $now,
            'old' => false,
            'current' => $now->format(self::DATE_STRING_FORMAT),
            'previous' => $now->modify('yesterday')->format(self::DATE_STRING_FORMAT)
        ];
    }

    private function getDateStringInfo(string $string, DateTimeZone $timezone)
    {
        $date = DateTimeImmutable::createFromFormat('!' . self::DATE_STRING_FORMAT, $string, $timezone);
        $now = new DateTimeImmutable('now', $timezone);
        $delay = $date->modify('tomorrow 00:03');
        $result = [
            'object' => $date,
            'old' => $now >= $delay,
            'current' => $string,
            'previous' => $date->modify('yesterday')->format(self::DATE_STRING_FORMAT)
        ];

        if ($result['old']) {
            $result['next'] = $date->modify('tomorrow')->format(self::DATE_STRING_FORMAT);
        }

        return $result;
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
