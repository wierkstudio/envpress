<?php

declare(strict_types=1);

namespace EnvPress\Layer;

/**
 * Configure a WordPress multisite network instance.
 */
class MultisiteLayer implements LayerInterface
{
    /**
     * The root path of a WordPress instance, omitting the trailing slash.
     *
     * @var string
     */
    private string $instancePath;

    /**
     * Create a new MultisiteLayer instance.
     *
     * @param string $instancePath
     *
     * @return void
     */
    private function __construct(string $instancePath)
    {
        $this->instancePath = $instancePath;
    }

    /**
     * Create a new MultisiteLayer instance.
     *
     * @param string $instancePath The root path of a WordPress instance,
     * omitting the trailing slash.
     *
     * @return MultisiteLayer
     */
    public static function create(string $instancePath): self
    {
        return new self($instancePath);
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
        return is_multisite();
    }

    /**
     * Apply the configuration of this layer.
     *
     * @return void
     */
    public function apply(): void
    {
        // Check if the WordPress install directory is located in a subdirectory
        if (
            ABSPATH !== $this->instancePath . '/' &&
            str_starts_with(ABSPATH, $this->instancePath . '/')
        ) {
            $this->applyCustomWordPressInstallDirFix();
        }
    }

    /**
     *
     *
     * @return void
     *
     * @see https://github.com/roots/multisite-url-fixer
     * @see https://core.trac.wordpress.org/ticket/36507
     */
    private function applyCustomWordPressInstallDirFix(): void
    {
        add_filter('option_home', [$this, 'filterHomeUrl']);
        add_filter('option_siteurl', [$this, 'filterSiteUrl']);
        add_filter('network_site_url', [$this, 'filterNetworkSiteUrl'], 10, 3);
        add_filter('style_loader_src', [$this, 'filterLoaderSrc'], 10, 2);
        add_filter('script_loader_src', [$this, 'filterLoaderSrc'], 10, 2);
    }

    /**
     * Derive the WordPress install dir URI component from the environment.
     *
     * Example: `/wp`
     *
     * @return string
     */
    private function getInstallDirUri(): string
    {
        return substr(ABSPATH, strlen($this->instancePath), -1);
    }

    /**
     * Remove the WordPress install subdirectory suffix from the home URL.
     *
     * Example: `https://example.com/wp` -> `https://example.com`
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function filterHomeUrl($value)
    {
        if (is_string($value)) {
            $installDir = $this->getInstallDirUri();
            if (str_ends_with($value, $installDir)) {
                $value = substr($value, 0, -strlen($installDir));
            }
        }
        return $value;
    }

    /**
     * Add the WordPress install subdirectory suffix to the site URL.
     *
     * Example: `https://example.com/site` -> `https://example.com/site/wp`
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function filterSiteUrl($value)
    {
        if (is_string($value)) {
            $installDir = $this->getInstallDirUri();
            if (!str_ends_with($value, $installDir)) {
                $value = $value . $installDir;
            }
        }
        return $value;
    }

    /**
     * Add the WordPress install subdirectory suffix to the network site URL,
     * before appending the path.
     *
     * Example: `https://example.com/wp-admin/network/` ->
     * `https://example.com/wp/wp-admin/network/`
     *
     * @param string $url
     * @param string $path
     * @param string $scheme
     *
     * @return string
     */
    public function filterNetworkSiteUrl(
        string $url,
        string $path,
        string|null $scheme
    ): string
    {
        $path = '/' . ltrim($path, '/');
        $url = substr($url, 0, strlen($url) - strlen($path));
        $installDir = $this->getInstallDirUri();
        if (!str_ends_with($url, $installDir)) {
            $url = $url . $installDir;
        }
        return $url . $path;
    }

    /**
     * Replace the site URL in style and script srcs with the URL to the
     * WordPress install dir.
     *
     * Example: `https://example.com/site/wp/example.css` -> `/wp/example.css`
     *
     * @param string $src
     * @param string $handle
     *
     * @return string
     */
    public function filterLoaderSrc(string $src, string $handle): string
    {
        $siteUrl = site_url();
        if (str_starts_with($src, $siteUrl)) {
            $src = $this->getInstallDirUri() . substr($src, strlen($siteUrl));
        }
        return $src;
    }
}
