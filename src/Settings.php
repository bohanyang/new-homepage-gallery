<?php

namespace App;

use Symfony\Component\HttpFoundation\Cookie;

use function time;

final class Settings
{
    private const COOKIES = [
        'image',
        'imagepreview'
    ];

    private const DEFAULTS = [
        '1366x768',
        '800x480'
    ];

    private const MAX_AGE = 157680000;

    /** @var CookieBuffer */
    private $cookieBuffer;

    public function __construct(CookieBuffer $cookieBuffer)
    {
        $this->cookieBuffer = $cookieBuffer;
    }

    public function getImageSize()
    {
        return $this->get(0);
    }

    public function setImageSize(string $value)
    {
        $this->set(0, $value);
    }

    public function getThumbnailSize()
    {
        return $this->get(1);
    }

    public function setThumbnailSize(string $value)
    {
        $this->set(1, $value);
    }

    private function get(string $key)
    {
        return $this->cookieBuffer->read(self::COOKIES[$key], self::DEFAULTS[$key]);
    }

    private function set(string $key, $value)
    {
        $this->cookieBuffer->send(new Cookie(self::COOKIES[$key], $value, time() + self::MAX_AGE));
    }

    public function export()
    {
        $results = [];

        foreach (self::COOKIES as $key => $cookie) {
            $value = $this->cookieBuffer->read($cookie);
            if ($value !== null) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    public function import(array $results)
    {
        foreach ($results as $key => $value) {
            if (isset(self::COOKIES[$key])) {
                $this->set($key, $value);
            }
        }
    }
}
