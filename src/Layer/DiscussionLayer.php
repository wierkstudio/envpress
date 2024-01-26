<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Util\Env;

/**
 * Configure discussion related aspects of a WordPress instance.
 */
class DiscussionLayer implements LayerInterface
{
    /**
     * Create a new DiscussionLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new DiscussionLayer instance.
     *
     * @return DiscussionLayer
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
        return true;
    }

    /**
     * Apply the configuration of this layer.
     *
     * @return void
     */
    public function apply(): void
    {
        if (!Env::getBool('DISCUSSION_COMMENTS', true)) {
            $this->disableComments();
        }

        if (!Env::getBool('DISCUSSION_OEMBED', true)) {
            $this->disableOembed();
        }
    }

    /**
     * Disable comments and related features.
     *
     * @return void
     */
    private function disableComments(): void
    {
        // Close comments on all posts
        add_filter('comments_open', '__return_false');

        // Hide existing comments
        add_filter('comments_array', '__return_empty_array');

        // Remove Comments Page in Admin
        add_action('admin_menu', function () {
            remove_menu_page('edit-comments.php');
        });

        // Remove Comments Link from Admin Bar
        add_action('wp_before_admin_bar_render', function () {
            global $wp_admin_bar;
            $wp_admin_bar->remove_menu('comments');
        });

        add_action('admin_init', function () {
            // Remove from post and page edit screens
            remove_meta_box('commentstatusdiv', 'post', 'normal');
            remove_meta_box('commentstatusdiv', 'page', 'normal');

            // Remove dashboard widget
            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
        });
    }

    /**
     * Disable oEmbed and related features.
     *
     * @return void
     */
    private function disableOembed(): void
    {
        // Remove oEmbed REST API endpoint
        remove_action('rest_api_init', 'wp_oembed_register_route');

        // Disable oEmbed link tag discovery
        add_filter('embed_oembed_discover', '__return_false');

        // Disable oEmbed discovery link tags
        remove_action('wp_head', 'wp_oembed_add_discovery_links');

        // Disable oEmbed related JavaScript from frontend and backend
        remove_action('wp_head', 'wp_oembed_add_host_js');
    }
}
