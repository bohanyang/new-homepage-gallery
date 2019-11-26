<?php

namespace App;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\ParameterBag;

class CookieBuffer
{
    /** @var ParameterBag */
    protected $cookieBag;

    /** @var Cookie[] */
    protected $buffer = [];

    public function setCookieBag(ParameterBag $cookieBag)
    {
        $this->cookieBag = $cookieBag;
    }

    public function read(string $key, $default = null)
    {
        return $this->cookieBag->get($key, $default);
    }

    public function send(Cookie $cookie)
    {
        $this->buffer[] = $cookie;
    }

    public function clear()
    {
        $buffer = $this->buffer;
        $this->buffer = [];

        return $buffer;
    }
}