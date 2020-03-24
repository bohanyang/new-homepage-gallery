<?php

namespace App;

use Base64Url\Base64Url;
use Exception;
use Throwable;

use function serialize;

class TestException extends Exception
{
    public function __construct($message = '', $code = 0, Throwable $previous = null, $context = [])
    {
        if ($context !== []) {
             $message .= ': ' . Base64Url::encode(serialize($context));
        }
        parent::__construct($message, $code, $previous);
    }
}
