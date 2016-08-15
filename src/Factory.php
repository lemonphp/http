<?php

namespace Lemon\Http;

use Lemon\Http\FactoryInterface;
use Lemon\Http\Message\ServerRequest;

class Factory implements FactoryInterface
{
    /**
     * @var array
     */
    protected $globals;

    public function __construct(array $globals)
    {
        $this->globals = $globals;
    }

    public function makeRequest()
    {
        $method = $this->globals['REQUEST_METHOD'];
        $uri = $this->makeUri();
        $headers = $this->makeHeaders();
        $cookies;
        $globals = $this->globals;
        $body = $this->makeRequestBody();
        $files = $this->makeUploadedFiles();
        $request = new ServerRequest($method, $uri, $headers, $cookies, $globals, $body, $files);
        // TODO: parse body

        return $request;
    }

    public function makeHeaders()
    {

    }

    public function makeRequestBody()
    {

    }

    public function makeUploadedFiles()
    {

    }

    public function makeUri()
    {

    }
}
