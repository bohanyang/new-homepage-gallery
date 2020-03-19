<?php

namespace App\EventSubscriber;

use App\LeanCloud;
use App\TestRoute;
use App\TestUser;
use LeanCloud\Engine\Cloud;
use LeanCloud\Engine\LeanEngine;
use LeanCloud\User;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

use function strpos;

class LeanEngineSubscriber extends LeanEngine implements EventSubscriberInterface, ServiceSubscriberInterface
{
    /** @var ContainerInterface */
    private $container;

    public function __construct(LeanCloud $lc, ContainerInterface $container)
    {
        $this->container = $container;
    }

    /** @var Request */
    protected $request;

    protected function getHeaderLine($key)
    {
        return $this->request->headers->get($key);
    }

    protected function getBody()
    {
        return $this->request->getContent();
    }

    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $this->request = $event->getRequest();
        $path = $this->request->getPathInfo();
        if (strpos($path, '/1.1/') === 0 || $path === '/__engine/1/ping') {
            $this->defineCloudFunctions();
            $this->dispatch($this->request->getMethod(), $path);
        }
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 200]
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedServices()
    {
        return ['test' => TestUser::class, 'route' => TestRoute::class];
    }

    public function defineCloudFunctions()
    {
        $functions = ['test','route'];

        foreach ($functions as $name) {
            Cloud::define(
                $name,
                function (array $params, ?User $user, array $meta) use ($name) {
                    return $this->container->get($name)($params, $user, $meta);
                }
            );
        }
    }
}
