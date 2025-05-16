<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Util\Env;
use EnvPress\Util\Plugin;
use WP_Error;

/**
 * Configure Sentry error reporting for backend and frontend.
 *
 * @see https://docs.sentry.io/platforms/php/
 */
class SentryLayer implements LayerInterface
{
    const ENV_VAR_KEY = 'SERVICE_SENTRY_DSN';

    /**
     * The Sentry DSN
     *
     * @var string|null
     */
    protected string|null $dsn = null;

    /**
     * The release version
     *
     * @var string|null
     */
    protected string|null $release = null;

    /**
     * The environment
     *
     * @var string|null
     */
    protected string|null $environment = null;

    /**
     * Create a new SentryLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Get the Sentry DSN.
     *
     * @return string|null
     */
    private function getSentryDsn(): string|null
    {
        if ($this->dsn === null) {
            $dsn = Env::getString(self::ENV_VAR_KEY, '');
            $this->dsn = $dsn !== '' ? $dsn : null;
        }
        return $this->dsn;
    }

    /**
     * Get the environment.
     *
     * @return string
     */
    private function getEnvironment(): string
    {
        if ($this->environment === null) {
            $this->environment =
                Env::getString('WP_ENVIRONMENT_TYPE', 'production');
        }
        return $this->environment;
    }

    /**
     * Get the release.
     *
     * @return string
     */
    private function getRelease(): string|null
    {
        if ($this->release === null) {
            $release = Env::getString('RELEASE_VERSION', '');
            $this->release = $release !== '' ? $release : null;
        }
        return $this->release;
    }

    /**
     * Create a new SentryLayer instance.
     *
     * @return SentryLayer
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
        return null;
    }

    /**
     * Decide on whether this layer should be applied based on the current
     * environment.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->getSentryDsn() !== null;
    }

    /**
     * Apply the configuration of this layer.
     *
     * @return void
     */
    public function apply(): void
    {
        // Configure Sentry (as soon as possible)
        if (function_exists('\Sentry\init')) {
            \Sentry\init([
                'dsn' => $this->getSentryDsn(),
                'environment' => $this->getEnvironment(),
                'release' => $this->getRelease(),
            ]);
        }

        // Use plugin utils to add hook outside of WordPress environment
        Plugin::addAction('muplugins_loaded', [$this, 'applyAfterMupluginsLoaded']);
    }

    /**
     * Apply the configuration of this layer after the `muplugins_loaded` hook.
     *
     * @return void
     */
    public function applyAfterMupluginsLoaded(): void
    {
        add_action('wp_mail_failed', [$this, 'captureWpError']);

        if (!is_admin()) {
            // Hook `wp_head` with priority 0 to ensure the Sentry scripts are
            // loaded before `wp_enqueue_scripts` (priority 1) is triggered.
            add_action('wp_head', [$this, 'applyHtmlCode'], 0);
        }
    }

    /**
     * Capture a WP_Error.
     *
     * @param WP_Error $error The error to capture
     *
     * @return void
     */
    public function captureWpError(WP_Error $error): void
    {
        if (function_exists('\Sentry\captureMessage')) {
            // TODO: Expand this naive implementation
            \Sentry\captureMessage('WP_Error: ' . $error->get_error_message());
        }
    }

    /**
     * Apply the frontend Sentry code.
     *
     * @return void
     */
    public function applyHtmlCode(): void
    {
        $options = [
            'dsn' => $this->getSentryDsn(),
            'environment' => $this->getEnvironment(),
            'release' => $this->getRelease(),
        ];
?>
<script
  src="https://browser.sentry-cdn.com/9.19.0/bundle.min.js"
  integrity="sha384-a5/JEdXBrJvePlxBAPLiPOjOu08fCqCX0LAknGJgSMUUJmeDd3dJM3yhAgdH1qFb"
  crossorigin="anonymous"
></script>
<script>Sentry.init(<?php echo json_encode($options); ?>)</script>
<?php
    }
}
