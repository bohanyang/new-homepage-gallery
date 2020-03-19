<?php

namespace App\EventSubscriber;

use App\CookieBuffer;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class CookieBufferSubscriber implements EventSubscriberInterface
{
    /**
     * @var CookieBuffer
     */
    private $cookieBuffer;

    public function __construct(CookieBuffer $cookieBuffer)
    {
        $this->cookieBuffer = $cookieBuffer;
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->cookieBuffer->setCookieBag($event->getRequest()->cookies);
    }

    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $response = $event->getResponse();

        $cookies = $this->cookieBuffer->clear();

        foreach ($cookies as $cookie) {
            $response->headers->setCookie($cookie);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', -1]
            ],
            KernelEvents::RESPONSE => 'onKernelResponse'
        ];
    }
}
