<?php

namespace App\Controller;

use App\Model\Date;
use App\Model\Image;
use App\Model\ImageView;
use App\Model\RecordView;
use App\Repository\NotFoundException;
use App\Repository\RepositoryInterface;
use App\Settings;
use BohanYang\BingWallpaper\Market;
use DateTimeZone;
use League\Uri\UriString;
use Safe\DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

use function compact;

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

    /** @var Expiration */
    private $exp;

    public function __construct(
        RepositoryInterface $repository,
        CacheInterface $cache,
        Settings $settings,
        ContainerBagInterface $params,
        Expiration $exp
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
        $this->exp = $exp;
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
        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            $data = $this->cache->get(
                "image.$name",
                function (ItemInterface $item) use ($now, $name) {
                    $item->expiresAt($this->exp->nextHour($now));
                    $image = $this->repository->getImage($name);

                    /** @var ImageView $image */
                    return [
                        'image' => $image,
                        'video_url' => $this->getVideoUrl($image)
                    ];
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        $data['image_origin'] = $this->params['image_origin'];
        $data['image_size'] = $this->settings->getImageSize();
        $data['flags'] = self::FLAGS;
        $data['date_format'] = self::DATE_STRING_FORMAT;

        return $this->render('image.html.twig', $data);
    }

    public function browse(string $page)
    {
        $limit = 15;
        $skip = $limit * ($page - 1);

        if ($page < 1) {
            throw $this->createNotFoundException();
        }

        $now = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        try {
            /** @var Image[] $images */
            $images = $this->cache->get(
                "browse.$page",
                function (ItemInterface $item) use ($now, $limit, $skip) {
                    $item->expiresAt($this->exp->nextHour($now));

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
        $now = new DateTimeImmutable('now', new DateTimeZone('Australia/Sydney'));

        try {
            $data = $this->cache->get(
                "date.${date}",
                function (ItemInterface $item) use ($now, $date) {
                    $date = $this->getDateStringInfo($date, $now);

                    if (!$date['old']) {
                        $item->expiresAt($this->exp->nextHour($now));
                    }

                    $images = $this->repository->findImagesByDate(Date::createFromYmd($date['current']));

                    /** @var ImageView[] $images */
                    return compact('images', 'date');
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        $data['image_origin'] = $this->params['image_origin'];
        $data['image_size'] = $this->settings->getThumbnailSize();
        $data['flags'] = self::FLAGS;

        return $this->render('date.html.twig', $data);
    }

    public function archive(string $market = '', string $date = '')
    {
        if ($market === '') {
            $market = self::DEFAULT_MARKET;
        }

        $market = new Market($market);
        $now = new DateTimeImmutable('now', $market->getTimeZone());

        try {
            $data = $this->cache->get(
                "archive.${market}.${date}",
                function (ItemInterface $item) use ($market, $date, $now) {
                    if ($date === '') {
                        $date = $this->getTodayInfo($now);
                    } else {
                        $date = $this->getDateStringInfo($date, $now);
                    }

                    if (!$date['old']) {
                        $item->expiresAt($this->exp->tomorrow($now));
                    }

                    $record = $this->repository->getRecord($market->getName(), Date::createFromYmd($date['current']));
                    $video = $this->getVideoUrl($record->image);

                    /** @var RecordView $record */
                    return compact('record', 'video', 'date');
                }
            );
        } catch (NotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage(), $e);
        }

        $data['image'] = $data['record']->image;
        $data['image_origin'] = $this->params['image_origin'];
        $data['image_size'] = $this->settings->getImageSize();

        return $this->render('archive.html.twig', $data);
    }

    private function getTodayInfo(DateTimeImmutable $now)
    {
        $delay = $this->exp->fixDelay($now->setTime(0, 3));

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

    private function getDateStringInfo(string $string, DateTimeImmutable $now)
    {
        $date = DateTimeImmutable::createFromFormat('!' . self::DATE_STRING_FORMAT, $string, $now->getTimezone());
        $delay = $this->exp->fixDelay($date->modify('tomorrow 00:03'));
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
