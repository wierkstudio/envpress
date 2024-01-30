<?php

declare(strict_types=1);

namespace EnvPress\Util;

/**
 * @internal
 */
final class URL
{
    /**
     * Parse a URL and return its components. In addition to the native
     * `parse_url` function, user name and password components are URL decoded
     * and the query string is parsed to an array of parameters.
     *
     * @param string $url URL string
     *
     * @return ?array Array with URL components or null, if invalid
     */
    public static function parseString(string $url): ?array
    {
        // Parse URL
        $rawData = parse_url($url);
        if ($rawData === false) {
            return null;
        }

        // Parse query parameters
        $query = [];
        parse_str($rawData['query'] ?? '', $query);

        // Decode URL encoded special characters in URI components
        $userName = rawurldecode($rawData['user'] ?? '');
        $password = rawurldecode($rawData['pass'] ?? '');

        return [
            'scheme' => strtolower($rawData['scheme'] ?? ''),
            'userName' => $userName,
            'password' => $password,
            'hostName' => strtolower($rawData['host'] ?? ''),
            'port' => $rawData['port'] ?? '',
            'path' => $rawData['path'] ?? '',
            'query' => $query,
        ];
    }
}
