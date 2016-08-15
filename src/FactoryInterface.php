<?php

namespace Lemon\Http;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UploadedFileInterface;

interface FactoryInterface
{
    /**
     * @return ServerRequestInterface
     */
    public function makeRequest();

    /**
     * @return UriInterface
     */
    public function makeUri();

    /**
     * @return array
     */
    public function makeHeaders();

    /**
     * @return StreamInterface
     */
    public function makeRequestBody();

    /**
     * @return UploadedFileInterface[]
     */
    public function makeUploadedFiles();
}
