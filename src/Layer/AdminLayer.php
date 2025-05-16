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

        add_filter('plugins_list', [$this, 'filterPluginsList']);

        $envDisplay = Env::getBool('ADMIN_DISPLAY_ENV', false);
        if ($envDisplay) {
            $this->applyDisplayEnv();
        }

        $idsString = Env::getString('ADMIN_DASHBOARD_DISABLE', '');
        if (!empty($idsString)) {
            $ids = explode(',', strtolower($idsString));
            add_action('wp_dashboard_setup', function () use ($ids) {
                $this->disableDashboardWidgets('dashboard', $ids);
            });
            add_action('wp_network_dashboard_setup', function () use ($ids) {
                $this->disableDashboardWidgets('dashboard-network', $ids);
            });
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

            // Support contact
            $supportName = Env::getString('ADMIN_SUPPORT_NAME', '');
            if ($supportName !== '') {
                $supportUrl = Env::getString('ADMIN_SUPPORT_URL', '');
                $facts[] = $supportUrl !== ''
                    ? "<a href=\"{$supportUrl}\" target=\"_blank\" " .
                      "rel=\"noopener\">{$supportName}</a>"
                    : $supportName;
            }

            // Release version
            $releaseVersion = Env::getString('RELEASE_VERSION', '');
            if ($releaseVersion !== '') {
                $releaseHtml = esc_html($releaseVersion);

                // Release URL
                $releaseUrl = Env::getString('RELEASE_URL', '');
                if ($releaseUrl !== '') {
                    $releaseHtml =
                        "<a href=\"{$releaseUrl}\" target=\"_blank\" " .
                        "rel=\"noopener\">{$releaseHtml}</a>";
                }

                $facts[] = "Release {$releaseHtml}";
            }

            // WordPress version
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

    /**
     * Disable admin screen widgets by the given ids.
     *
     * @param string $screen Screen id
     * @param array $ids Array of meta box ids
     *
     * @return void
     */
    private function disableDashboardWidgets(string $screen, array $ids): void
    {
        global $wp_meta_boxes;
        foreach ($ids as $id) {
            foreach (['normal', 'side', 'column3', 'column4'] as $context) {
                foreach (['high', 'core', 'default', 'low'] as $prio) {
                    if (!empty($wp_meta_boxes[$screen][$context][$prio][$id])) {
                        remove_meta_box($id, $screen, $context);
                    }
                }
            }
        }
    }

    /**
     * Filter the plugins list to document the package as an active
     * must-use plugin.
     *
     * @param array $plugins
     *
     * @return array
     */
    public function filterPluginsList(array $plugins): array
    {
        if (!empty($plugins['mustuse'])) {
            $plugins['mustuse']['envpress'] = [
                'Name' => 'EnvPress',
                'PluginURI' => 'https://github.com/wierkstudio/envpress',
                'Version' => '',
                'Description' =>
                    'A PHP package streamlining the configuration of modern ' .
                    'and secure WordPress instances using a standard set of ' .
                    'environment variables. ' .
                    'Loaded via <code>wp-config.php</code>.',
                'Author' => 'Wierk',
                'AuthorURI' =>
                    'https://wierk.lu/?utm_source=envpress&utm_medium=referral&utm_campaign=open_source_projects',
                'TextDomain' => '',
                'DomainPath' => '',
                'Network' => false,
                'RequiresWP' => '',
                'RequiresPHP' => '',
                'UpdateURI' => '',
                'RequiresPlugins' => '',
                'Title' => 'EnvPress',
                'AuthorName' => 'Wierk',
            ];
        }
        return $plugins;
    }
}
