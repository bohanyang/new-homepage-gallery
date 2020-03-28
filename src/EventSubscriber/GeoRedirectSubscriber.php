<?php

namespace App\EventSubscriber;

use App\CookieBuffer;
use App\GeoIP\CheckerInterface;
use App\Settings;
use InvalidArgumentException;
use League\Uri\Components\Query;
use League\Uri\Uri;
use League\Uri\UriModifier;
use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

final class GeoRedirectSubscriber implements EventSubscriberInterface, ServiceSubscriberInterface
{
    private const NO_REDIRECT_COOKIE = '_f0ca47';

    private const QUERY_PARAM = '_settings';

    private const MAX_AGE = 604800;

    /** @var CookieBuffer */
    private $cookieBuffer;

    /** @var Settings */
    private $settings;

    /** @var ContainerInterface */
    private $container;

    private $destination;

    public function __construct(
        CookieBuffer $cookieBuffer,
        Settings $settings,
        ContainerInterface $container,
        ContainerBagInterface $params
    ) {
        $this->cookieBuffer = $cookieBuffer;
        $this->settings = $settings;
        $this->container = $container;
        $this->destination = $params->has('geo_redirect.destination') ? $params->get('geo_redirect.destination') : null;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', -1]
            ]
        ];
    }

    private function isGeoSpecific()
    {
        return !isset($this->destination);
    }

    private function geoRedirect(RequestEvent $event)
    {
        $request = $event->getRequest();
        $ip = $request->getClientIp();

        if ($this->cookieBuffer->read(self::NO_REDIRECT_COOKIE) === $ip) {
            return;
        }

        if (!$this->container->get('checker')->isCN($ip)) {
            $this->cookieBuffer->send(
                new Cookie(
                    self::NO_REDIRECT_COOKIE,
                    $ip,
                    time() + self::MAX_AGE
                )
            );

            return;
        }

        $settings = $this->settings->export();

        $uri = Uri::createFromString((string) $this->destination . $request->getPathInfo());
        $query = $request->getQueryString();

        if ($settings !== []) {
            $query = Query::createFromRFC3986($query)->withPair(
                self::QUERY_PARAM,
                self::urlSafeBase64Encode(self::serializeData($settings))
            )->getContent();
        }

        $uri = $uri->withQuery($query)->__toString();

        $event->setResponse(new RedirectResponse($uri));
    }

    private function geoMigrate(RequestEvent $event)
    {
        $request = $event->getRequest();

        if ($request->query->has(self::QUERY_PARAM)) {
            $this->settings->import(
                self::unserializeData(
                    self::urlSafeBase64Decode(
                        $request->query->get(self::QUERY_PARAM)
                    )
                )
            );

            $uri = Uri::createFromString($request->getUri());
            $uri = UriModifier::removePairs($uri, self::QUERY_PARAM)->__toString();

            $event->setResponse(
                new RedirectResponse(
                    $uri
                )
            );
        }
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($this->isGeoSpecific()) {
            $this->geoMigrate($event);

            return;
        }

        $this->geoRedirect($event);
    }

    private static function serializeData($data)
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    private static function unserializeData($data)
    {
        $data = json_decode($data, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException(json_last_error_msg());
        }

        return $data;
    }

    private static function urlSafeBase64Encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function urlSafeBase64Decode($data)
    {
        $data = base64_decode(strtr($data, '-_', '+/'), true);

        if ($data === false) {
            throw new InvalidArgumentException('Data is invalid.');
        }

        return $data;
    }

    public static function getSubscribedServices()
    {
        return [
            'checker' => CheckerInterface::class
        ];
    }
}