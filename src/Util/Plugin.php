<?php

declare(strict_types=1);

namespace EnvPress\Util;

/**
 * @internal
 */
final class Plugin
{
    /**
     * Adds a callback function to a filter hook.
     *
     * Closely mimicks the native WordPress function `add_filter`.
     * If the function `add_filter` is present, it is called internally.
     * If not, the hook is added to the `$wp_filter` global variable.
     *
     * @param string $hookName The name of the filter to add the callback to.
     * @param callable $callback The callback to be run when the filter is
     * applied.
     * @param int $priority Used to specify the order in which the functions
     * associated with a particular filter are executed. Lower numbers
     * correspond with earlier execution, and functions with the same priority
     * are executed in the order in which they were added to the filter.
     * Default: 10
     * @param int $acceptedArgs The number of arguments the function accepts.
     * Default: 1
     *
     * @return bool Always returns true.
     *
     * @see https://developer.wordpress.org/reference/classes/wp_hook/build_preinitialized_hooks/
     */
    public static function addFilter(
        string $hookName,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {
        global $wp_filter;
        if (function_exists('add_filter')) {
            return \add_filter($hookName, $callback, $priority, $acceptedArgs);
        }
        if (!$wp_filter) {
            $wp_filter = [];
        }
        if (!isset($wp_filter[$hookName])) {
            $wp_filter[$hookName] = [];
        }
        if (!isset($wp_filter[$hookName][$priority])) {
            $wp_filter[$hookName][$priority] = [];
        }
        $wp_filter[$hookName][$priority][] = [
            'accepted_args' => $acceptedArgs,
            'function' => $callback
        ];
        return true;
    }

    /**
     * Adds a callback function to an action hook.
     *
     * Closely mimicks the native WordPress function `add_action`.
     * If the function `add_action` is present, it is called internally.
     *
     * @param string $hookName The name of the filter to add the callback to.
     * @param callable $callback The callback to be run when the filter is
     * applied.
     * @param int $priority Used to specify the order in which the functions
     * associated with a particular filter are executed. Lower numbers
     * correspond with earlier execution, and functions with the same priority
     * are executed in the order in which they were added to the filter.
     * Default: 10
     * @param int $acceptedArgs The number of arguments the function accepts.
     * Default: 1
     *
     * @return bool Always returns true.
     */
    public static function addAction(
        string $hookName,
        callable $callback,
        int $priority = 10,
        int $acceptedArgs = 1
    ): bool {
        if (function_exists('add_action')) {
            return \add_action($hookName, $callback, $priority, $acceptedArgs);
        }
        return self::addFilter($hookName, $callback, $priority, $acceptedArgs);
    }
}
