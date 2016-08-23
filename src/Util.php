<?php

namespace Lemon\Http;

class Util
{
    /**
     * @var array
     */
    public static $validHTTPVerions = [
        '1.0' => true,
        '1.1' => true,
        '2.0' => true,
    ];

    /**
     * @var array
     */
    public static $validHTTPMethods = [
        'CONNECT' => true,
        'DELETE' => true,
        'GET' => true,
        'HEAD' => true,
        'OPTIONS' => true,
        'PATCH' => true,
        'POST' => true,
        'PUT' => true,
        'TRACE' => true,
    ];

    /**
     * @var array
     */
    public static $validUriSchemes = [
        '' => true,
        'https' => true,
        'http' => true,
    ];

    /**
     * Normalize HTTP protocol version
     *
     * @param string $version
     * @return string
     * @throws \InvalidArgumentException If the HTTP version is invalid.
     */
    public static function normalizeHTTPVersion($version)
    {
        if (!isset(self::$validHTTPVerions[$version])) {
            throw new \InvalidArgumentException(
                'Invalid HTTP version. Must be one of: ' .implode(', ', self::supportedHTTPVersions())
            );
        }

        return $version;
    }

    /**
     * Get supported HTTP protocol version list
     *
     * @return array
     */
    public static function supportedHTTPVersions()
    {
        return array_keys(self::$validHTTPVerions);
    }

    /**
     * Validate the HTTP method
     *
     * @param  null|string $method
     * @return null|string
     * @throws \InvalidArgumentException on invalid HTTP method.
     */
    public static function normalizeHTTPMethod($method)
    {
        if ($method === null) {
            return $method;
        }

        if (!is_string($method) && !method_exists($method, '__toString')) {
            throw new \InvalidArgumentException(
                'Unsupported HTTP method; must be a string'
            );
        }

        $method = strtoupper((string)$method);
        if (!isset(self::$validHTTPMethods[$method])) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported HTTP method "%s" provided',
                $method
            ));
        }

        return $method;
    }

    /**
     * Normalize HTTP status code.
     *
     * @param  int $status HTTP status code.
     * @return int
     * @throws \InvalidArgumentException If an invalid HTTP status code is provided.
     */
    public static function normalizeHTTPStatusCode($status)
    {
        if (!is_integer($status) || $status<100 || $status>599) {
            throw new \InvalidArgumentException('Invalid HTTP status code');
        }

        return $status;
    }

    /**
     * Normalize header name
     *
     * This method transforms header names into a
     * normalized form. This is how we enable case-insensitive
     * header names in the other methods in this class.
     *
     * @param  string $name The case-insensitive header name
     * @return string Normalized header name
     */
    public static function normalizeHeaderName($name)
    {
        $name = strtr(strtolower($name), '_', '-');
        if (strpos($name, 'http-') === 0) {
            $name = substr($name, 5);
        }

        return $name;
    }

    /**
     * Normalize URI scheme
     *
     * This method transforms URI scheme into a normalized form and validate it
     * with supported schemes.
     *
     * @param string $scheme
     * @return string
     * @throws \InvalidArgumentException for invalid or unsupported schemes.
     */
    public static function normalizeUriScheme($scheme)
    {
        if (!is_string($scheme) && !method_exists($scheme, '__toString')) {
            throw new \InvalidArgumentException('Uri scheme must be a string');
        }

        $scheme = str_replace('://', '', strtolower((string) $scheme));

        if (!isset(self::$validUriSchemes[$scheme])) {
            throw new \InvalidArgumentException(
                'Uri scheme must be one of: "' . implode('", "', self::supportedUriSchemes()) . '"'
            );
        }

        return $scheme;
    }

    /**
     * Get supported URI scheme list
     *
     * @return array
     */
    public static function supportedUriSchemes()
    {
        return array_keys(self::$validUriSchemes);
    }

    /**
     * Normalize Uri port.
     *
     * Allow NULL value or an integer number between 1 and 65535
     *
     * @param  null|int $port The Uri port number.
     * @return null|int
     * @throws InvalidArgumentException If the port is invalid.
     */
    public static function normalizeUriPort($port)
    {
        if (is_null($port) || (is_integer($port) && ($port >= 1 && $port <= 65535))) {
            return $port;
        }

        throw new \InvalidArgumentException(
            'Uri port must be null or an integer between 1 and 65535 (inclusive)'
        );
    }

    /**
     * Normalize Uri path.
     *
     * This method percent-encodes all reserved
     * characters in the provided path string. This method
     * will NOT double-encode characters that are already
     * percent-encoded.
     *
     * @param  string $path The raw uri path.
     * @return string       The RFC 3986 percent-encoded uri path.
     * @throws \InvalidArgumentException for invalid paths.
     * @link   http://www.faqs.org/rfcs/rfc3986.html
     */
    public static function normalizeUriPath($path)
    {
        if (!is_string($path) && !method_exists($path, '__toString')) {
            throw new \InvalidArgumentException('Uri path must be a string');
        }

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            $path
        );
    }

    /**
     * Normalize the query string of a URI.
     *
     * @param string $query The raw uri query string.
     * @return string The percent-encoded query string.
     * @throws \InvalidArgumentException for invalid query strings.
     */
    public static function normalizeUriQuery($query)
    {
        if (!is_string($query) && !method_exists($query, '__toString')) {
            throw new \InvalidArgumentException('Uri query must be a string');
        }

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            ltrim((string)$query, '?')
        );
    }

    /**
     * Normalize the fragment of a URI.
     *
     * @param string $fragment The raw uri fragment string.
     * @return string The percent-encoded fragment string.
     * @throws \InvalidArgumentException for invalid fragment strings.
     */
    public static function normalizeUriFragment($fragment)
    {
        if (!is_string($fragment) && !method_exists($fragment, '__toString')) {
            throw new \InvalidArgumentException('Uri fragment must be a string');
        }

        return preg_replace_callback(
            '/(?:[^a-zA-Z0-9_\-\.~!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))/',
            function ($match) {
                return rawurlencode($match[0]);
            },
            ltrim((string)$fragment, '#')
        );
    }

    /**
     * Normalize the request target
     *
     * @param string $requestTarget
     * @return string
     * @throws \InvalidArgumentException if the request target is invalid
     */
    public static function normalizeRequestTarget($requestTarget)
    {
        if (!is_string($requestTarget) && !method_exists($requestTarget, '__toString')) {
            throw new \InvalidArgumentException('Uri fragment must be a string');
        }
        $requestTarget = (string) $requestTarget;

        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }

        return $requestTarget;
    }
}
