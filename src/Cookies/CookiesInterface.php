<?php

namespace Lemon\Http\Cookies;

interface CookiesInterface
{

    public function get($name, $default = null);

    public function set($name, $value);

    public function getHeaderLine();

    public static function parseHeader($header);
}
