<?php

namespace App;

use Symfony\Component\HttpFoundation\Cookie;

use function time;

class Settings
{
    public const COOKIES = [
        'imageSize' => 'image',
        'thumbnailSize' => 'imagepreview'
    ];

    public const DEFAULTS = [
        'imageSize' => '1366x768',
        'thumbnailSize' => '800x480'
    ];

    /**
     * @var CookieBuffer
     */
    protected $cookieBuffer;

    public function __construct(CookieBuffer $cookieBuffer)
    {
        $this->cookieBuffer = $cookieBuffer;
    }

    public function getImageSize()
    {
        return $this->get('imageSize');
    }

    public function setImageSize(string $value)
    {
        $this->set('imageSize', $value);
    }

    public function getThumbnailSize()
    {
        return $this->get('thumbnailSize');
    }

    public function setThumbnailSize(string $value)
    {
        $this->set('thumbnailSize', $value);
    }

    protected function get(string $key)
    {
        return $this->cookieBuffer->read(static::COOKIES[$key], static::DEFAULTS[$key]);
    }

    protected function set(string $key, $value)
    {
        $expire = time() + 3600 * 24 * 365 * 5;

        $this->cookieBuffer->send(new Cookie(static::COOKIES[$key], $value, $expire));
    }
}
