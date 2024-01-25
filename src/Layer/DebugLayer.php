<?php

declare(strict_types=1);

namespace EnvPress\Layer;

/**
 * Configure a WordPress instance in debugging mode.
 */
class DebugLayer implements LayerInterface
{
    /**
     * Create a new DebugLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new DebugLayer instance.
     *
     * @return DebugLayer
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Return the name of a WordPress hook from which this layer should be
     * applied. If null is returned, the layer should be applied immediately.
     *
     * @return string|null
     */
    public function getHookName(): string|null
    {
        return 'muplugins_loaded';
    }

    /**
     * Decide on whether this layer should be applied based on the current
     * environment.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * Apply the configuration of this layer.
     *
     * @return void
     */
    public function apply(): void
    {
        if (is_admin()) {
            $this->applyUnhandledErrorAdminNotices();
        }
    }

    /**
     * In debug mode, show the last unhandled error rendering an admin screen.
     *
     * @return void
     */
    private function applyUnhandledErrorAdminNotices(): void
    {
        add_action('admin_notices', function () {
            if (($error = error_get_last()) !== null) {
                $message = esc_html($error['message']);
                $file = $error['file'] . ':' . $error['line'];
                echo (
                    "<div class=\"notice notice-warning is-dismissible\">" .
                    "<p><strong>An unhandled error occurred</strong></p>" .
                    "<p>{$message}</p>" .
                    "<p><code>{$file}</code></p>" .
                    "</div>"
                );
            }
        });
    }
}
