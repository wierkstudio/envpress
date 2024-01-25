<?php

declare(strict_types=1);

namespace EnvPress\Util;

use Dotenv\Dotenv;
use Dotenv\Exception\ExceptionInterface as DotenvExceptionInterface;
use EnvPress\Exception\InvalidDotenvException;
use EnvPress\Exception\UnexpectedEnvVarTypeException;

/**
 * @internal
 */
final class Env
{
    /**
     * Return an environment type of the given key converted to a simple type.
     *
     * - String `true` is converted to boolean `true`
     * - String `false` is converted to boolean `false`
     * - String `null` is converted to `null`
     * - String only containing digits is converted to an integer
     * - String wrapped in `"`-quotes are interpreted as JSON string
     *
     * @param string $key Key of environment variable
     * @param string|int|bool|null $default Fallback value, if not present
     *
     * @return string|int|bool|null
     */
    public static function get(
        string $key,
        string|int|bool|null $default = null
    ): string|int|bool|null
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? null;
        if ($value === null) {
            return $default;
        }
        return self::convertValue($value);
    }

    /**
     * Return a bool environment variable.
     *
     * @param string $key Key of environment variable
     * @param bool $default Fallback value, if not present
     *
     * @return bool
     * @throws UnexpectedEnvVarTypeException
     */
    public static function getBool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);
        if (is_bool($value)) {
            return $value;
        }
        throw new UnexpectedEnvVarTypeException(
            "Env var {$key} is expected to contain a bool"
        );
    }

    /**
     * Return a string environment variable.
     *
     * @param string $key Key of environment variable
     * @param string $default Fallback value, if not present
     *
     * @return string
     * @throws UnexpectedEnvVarTypeException
     */
    public static function getString(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);
        if (is_string($value) || is_int($value)) {
            return (string) $value;
        }
        throw new UnexpectedEnvVarTypeException(
            "Env var {$key} is expected to contain a string"
        );
    }

    /**
     * Return an int environment variable.
     *
     * @param string $key Key of environment variable
     * @param int $default Fallback value, if not present
     *
     * @return int
     * @throws UnexpectedEnvVarTypeException
     */
    public static function getInt(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);
        if (is_int($value)) {
            return $value;
        }
        throw new UnexpectedEnvVarTypeException(
            "Env var {$key} is expected to contain an int"
        );
    }

    /**
     * Convert a string value to a simple type.
     *
     * @param string $value
     *
     * @return string|int|bool|null
     */
    private static function convertValue(string $value): string|int|bool|null
    {
        $lowerCaseValue = strtolower($value);
        if ($lowerCaseValue === 'true') {
            return true;
        } else if ($lowerCaseValue === 'false') {
            return false;
        } else if ($lowerCaseValue === 'null') {
            return null;
        } else if (ctype_digit($value)) {
            return intval($value);
        } else if (str_starts_with($value, '"') && str_ends_with($value, '"')) {
            return self::convertValue((string) json_decode($value));
        } else {
            return $value;
        }
    }

    /**
     * Load environment variables from a .env file.
     *
     * @param string $dotEnvPath The path to the folder containing the .env,
     * omitting the trailing slash.
     *
     * @return void
     * @throws InvalidDotenvException
     */
    public static function loadDotEnv(string $dotEnvPath): void
    {
        if (file_exists($dotEnvPath . '/.env')) {
            try {
                $dotenv = Dotenv::createImmutable($dotEnvPath);
                $dotenv->load();
            } catch (DotenvExceptionInterface $dotenvException) {
                throw new InvalidDotenvException(
                    $dotenvException->getMessage()
                );
            }
        }
    }

    /**
     * Detect and validate a proxy remote to change `$_SERVER` facts, like
     * remote address, server port, and server protocol, accordingly. Only
     * proxies that are listed in `ENVPRESS_TRUSTED_PROXIES` are detected.
     *
     * @return void
     */
    public static function loadProxyEnv(): void
    {
        $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $trustedProxiesValue = self::getString('ENVPRESS_TRUSTED_PROXIES', '');
        $trustedProxyAddresses = preg_split('/\s*,\s*/', $trustedProxiesValue);

        $trustworthyProxy = in_array($remoteAddress, $trustedProxyAddresses);
        if (!$trustworthyProxy) {
            return;
        }

        // Original remote IP address
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $headerValue = $_SERVER['HTTP_X_FORWARDED_FOR'];

            // The forwarded for header may contain multiple ip addresses
            // to include the addresses of one or more proxies
            $entries = preg_split('/\s*,\s*/', $headerValue);
            if (!empty($entries[0])) {
                $_SERVER['REMOTE_ADDR'] = $entries[0];
            }
        }

        // Original port
        if (!empty($_SERVER['HTTP_X_FORWARDED_PORT'])) {
            $_SERVER['SERVER_PORT'] = $_SERVER['HTTP_X_FORWARDED_PORT'];
        }

        // Original protocol/scheme
        if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
            $usingHttps = $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https';
            $_SERVER['REQUEST_SCHEME'] = $usingHttps ? 'https' : 'http';
            // See https://developer.wordpress.org/reference/functions/is_ssl/
            $_SERVER['HTTPS'] = $usingHttps ? 'on' : '';
        }
    }
}
