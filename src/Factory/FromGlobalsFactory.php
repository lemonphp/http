<?php

namespace Lemon\Http;

use Lemon\Http\Factory\FactoryInterface;
use Lemon\Http\Message\ServerRequest;

class FromGlobalsFactory implements FactoryInterface
{
    /**
     * List body parser
     *
     * @var array
     */
    protected $parsers = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerParser('application/json',
            function ($input) {
            return json_decode($input, true);
        });

        $this->registerParser('application/xml',
            function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);

            return $result;
        });

        $this->registerParser('text/xml',
            function ($input) {
            $backup = libxml_disable_entity_loader(true);
            $result = simplexml_load_string($input);
            libxml_disable_entity_loader($backup);

            return $result;
        });

        $this->registerParser('application/x-www-form-urlencoded',
            function ($input) {
            $data = [];
            parse_str($input, $data);

            return $data;
        });
    }

    /**
     * Make server request
     *
     * @return ServerRequest
     */
    public function make()
    {
        $requestBody = (string) $request->getBody();
        $contentType = $this->getContentType($request);
        if (!$requestBody && !$contentType && isset($this->parsers[$contentType])) {
            $parsedBody = call_user_func($this->parsers[$contentType],
                $requestBody);

            $request = $request->withParsedBody($parsedBody);
        }

        return $request;
    }

    /**
     * Register a request body parser
     *
     * @param string   $contentType
     * @param callable $parser
     */
    public function registerParser($contentType, callable $parser)
    {
        if ($parser instanceof Closure) {
            $parser = $parser->bindTo($this);
        }
        $this->parsers[strtolower((string) $contentType)] = $parser;
    }

    /**
     * Get request content type
     *
     * @param ServerRequestInterface $request
     * @return string|null
     */
    private function getContentType(ServerRequestInterface $request)
    {
        $result = $request->getHeaderLine('Content-Type');
        $types  = preg_split('/\s*[;,]\s*/', $result);

        return empty($types) ? null : strtolower($types[0]);
    }

    public static function createResponse()
    {

    }

    public static function createServerRequest()
    {

    }
}
