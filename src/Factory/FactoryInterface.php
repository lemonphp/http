<?php

namespace Lemon\Http\Factory;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

interface FactoryInterface
{
    /**
     * @return ServerRequestInterface
     */
    public static function createServerRequest();

    /**
     * @return ResponseInterface
     */
    public static function createResponse();
}
