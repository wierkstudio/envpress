<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Util\Env;

/**
 * Configure admin related additions to a WordPress instance.
 */
class AdminLayer implements LayerInterface
{
    /**
     * Create a new AdminLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new AdminLayer instance.
     *
     * @return AdminLayer
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
        return 'admin_init';
    }

    /**
     * Decide on whether this layer should be applied based on the current
     * environment.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return is_admin();
    }

    /**
     * Apply the configuration of this layer.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->applyFooterText();

        $envDisplay = Env::getBool('ADMIN_DISPLAY_ENV', false);
        if ($envDisplay) {
            $this->applyDisplayEnv();
        }
    }

    /**
     * Configure admin footer text to include a reference to the supporter and
     * the current WordPress version.
     *
     * @return void
     */
    private function applyFooterText(): void
    {
        remove_filter('update_footer', 'core_update_footer');
        add_filter('admin_footer_text', function (string $text): string {
            $facts = [];

            // Prepend support details
            $supportName = Env::getString('ADMIN_SUPPORT_NAME', '');
            if ($supportName !== '') {
                $supportUrl = Env::getString('ADMIN_SUPPORT_URL', '');
                $facts[] = $supportUrl !== ''
                    ? "<a href=\"{$supportUrl}\" target=\"_blank\" " .
                      "rel=\"noopener\">{$supportName}</a>"
                    : $supportName;
            }

            // TODO: Prepend release version

            // Inject WordPress version
            $version = get_bloginfo('version', 'display');
            $facts[] = "WordPress {$version}";

            return implode(' | ', $facts);
        });
    }

    /**
     * Make sites with multiple environment types distinguishable by adding a
     * colored badge to the trailing end of the admin bar.
     *
     * @return void
     *
     * @see https://developer.wordpress.org/reference/functions/wp_get_environment_type/
     */
    private function applyDisplayEnv(): void
    {
        $envTypeColorsMap = [
            'local' => [
                'background' => '#2271b1',
                'color' => '#ffffff',
            ],
            'development' => [
                'background' => '#00a32a',
                'color' => '#ffffff',
            ],
            'staging' => [
                'background' => '#dba617',
                'color' => '#1d2327',
            ],
            'production' => [
                'background' => '#d63638',
                'color' => '#ffffff',
            ],
        ];

        add_action('admin_bar_menu', function () use ($envTypeColorsMap) {
            global $wp_admin_bar;
            $environmentType = wp_get_environment_type();
            $colors = $envTypeColorsMap[$environmentType];
            $wp_admin_bar->add_node([
                'id' => 'envpress-env',
                'parent' => 'top-secondary',
                'title' => ucfirst($environmentType),
                'meta' => [
                    'class' => 'envpress-admin-bar-env',
                    'html' =>
                        "<style>" .
                        ".envpress-admin-bar-env, " .
                        ".envpress-admin-bar-env .ab-item { " .
                        "background: {$colors['background']} !important; " .
                        "color: {$colors['color']} !important; " .
                        "}</style>"
                ],
            ]);
        }, 1);
    }
}
