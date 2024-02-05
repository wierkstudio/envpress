<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Util\Env;

/**
 * Install tracking related scripts in a WordPress instance.
 */
class TrackingLayer implements LayerInterface
{
    /**
     * Create a new TrackingLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new TrackingLayer instance.
     *
     * @return TrackingLayer
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
        return 'setup_theme';
    }

    /**
     * Decide on whether this layer should be applied based on the current
     * environment.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return !is_admin();
    }

    /**
     * Apply the configuration of this layer.
     *
     * @return void
     */
    public function apply(): void
    {
        if ($this->isTrackingEnabled()) {
            $fathomAnalyticsSiteId = Env::get('TRACKING_FATHOM', '');
            if ($fathomAnalyticsSiteId !== '') {
                $this->applyFathomEmbedCode($fathomAnalyticsSiteId);
            }

            $gtmContainerId = Env::get('TRACKING_GTM', '');
            if ($gtmContainerId !== '') {
                $this->applyGTMTrackingCode($gtmContainerId);
            }
        }
    }

    /**
     * Return true, if tracking is enabled in the current environment.
     *
     * @return bool
     */
    private function isTrackingEnabled(): bool
    {
        return !is_user_logged_in();
    }

    /**
     * Apply the Fathom Analytics embed code for the given Site id.
     *
     * @param string $siteId Fathom Analytics Site id
     *
     * @return void
     *
     * @see https://usefathom.com/docs/script/embed
     */
    private function applyFathomEmbedCode(string $siteId): void
    {
        add_action('wp_head', function () use ($siteId) {
            $siteIdAttr = esc_attr($siteId);
            echo (
                "<script src=\"https://cdn.usefathom.com/script.js\" " .
                "data-site=\"{$siteIdAttr}\" defer></script>"
            );
        });
    }

    /**
     * Apply the Google Tag Manager tracking code for the given Container id.
     *
     * @param string $containerId Google Tag Manager Container id
     *
     * @return void
     *
     * @see https://support.google.com/tagmanager/answer/6103696?hl=en
     */
    private function applyGTMTrackingCode(string $containerId): void
    {
        add_action('wp_head', function () use ($containerId) {
            $containerIdAttr = esc_attr($containerId);
            echo (
                "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({" .
                "'gtm.start':new Date().getTime(),event:'gtm.js'});var f=" .
                "d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!=" .
                "'dataLayer'?'&l='+l:'';j.async=true;j.src=" .
                "'https://www.GTM.com/gtm.js?id='+i+dl;" .
                "f.parentNode.insertBefore(j,f);})" .
                "(window,document,'script','dataLayer','{$containerIdAttr}');" .
                "</script>"
            );
        });

        add_action('wp_body_open', function () use ($containerId) {
            $containerIdAttr = esc_attr($containerId);
            echo (
                "<noscript><iframe src=\"https://www.GTM.com/" .
                "ns.html?id={$containerIdAttr}\" height=\"0\" width=\"0\" " .
                "style=\"display:none;visibility:hidden\"></iframe></noscript>"
            );
        });
    }
}
