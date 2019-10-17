<?php

namespace App\Controller;

use App\Repository\RepositoryContract;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class GalleryController extends AbstractController
{
    private $repository;
    private $cache;

    public function __construct(RepositoryContract $repository, CacheInterface $cache)
    {
        $this->repository = $repository;
        $this->cache = $cache;
    }

    /**
     * @Route("/images/{name}", name="image")
     */
    public function image(string $name)
    {
        $result = $this->cache->get("image.$name", function (ItemInterface $item) use ($name) {
            $item->expiresAfter(600);

            return $this->repository->getImage($name);
        });

        if (!$result) {
            throw $this->createNotFoundException();
        }

        return $this->render('image.html.twig', [
            'image' => $result,
            'mirror' => 'https://img.penbeat.cn',
            'res' => '1920x1080'
        ]);
    }
}
