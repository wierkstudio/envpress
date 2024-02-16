<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Exception\InvalidEnvVarException;
use EnvPress\Exception\UnsupportedSchemeException;
use EnvPress\Util\Env;

/**
 * Configuration layer at which WordPress constants are setup.
 * In here, the WordPress library is not available, yet.
 */
class ConstLayer implements LayerInterface
{
    /**
     * The root path of a WordPress instance, omitting the trailing slash.
     *
     * @var string
     */
    private string $instancePath;

    /**
     * The path to the WordPress core files, omitting the trailing slash.
     *
     * @var string
     */
    private string $absPath;

    /**
     * The path to the WordPress content directory, omitting the trailing slash.
     *
     * @var string
     */
    private string $contentPath;

    /**
     * Create a new ConstLayer instance.
     *
     * @param string $instancePath
     * @param string $absPath
     * @param string $contentPath
     *
     * @return void
     */
    private function __construct(
        string $instancePath,
        string $absPath,
        string $contentPath
    ) {
        $this->instancePath = $instancePath;
        $this->absPath = $absPath;
        $this->contentPath = $contentPath;
    }

    /**
     * Create a new ConstLayer instance.
     *
     * @param string $instancePath The root path of a WordPress instance,
     * omitting the trailing slash.
     * @param string $absPath The path to the WordPress core files, omitting the
     * trailing slash.
     * @param string $contentPath The path to the WordPress content directory,
     * omitting the trailing slash.
     *
     * @return ConstLayer
     */
    public static function create(
        string $instancePath,
        string $absPath,
        string $contentPath
    ): self
    {
        return new self($instancePath, $absPath, $contentPath);
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
        return true;
    }

    /**
     * Apply all configuration steps in this phase.
     *
     * @return void
     */
    public function apply(): void
    {
        $this->applyPathsConfig();
        $this->applyDatabaseConfig();
        $this->applySaltsConfig();
        $this->applyEnvironmentConfig();
        $this->applyPluginConfig();
    }

    /**
     * Configure WordPress path constants.
     *
     * @return void
     *
     * @see https://developer.wordpress.org/reference/functions/wp_plugin_directory_constants/
     */
    private function applyPathsConfig(): void
    {
        // URL the WordPress instance can be reached at
        define('WP_HOME', Env::getString('WP_HOME'));

        // URL where WordPress core files reside
        define('WP_SITEURL', Env::getString('WP_SITEURL'));

        // Derive the content URL from the content path
        $contentUrl =
            WP_HOME .
            substr($this->contentPath, strlen($this->instancePath));

        // Content directory
        define('WP_CONTENT_DIR', $this->contentPath);
        define('WP_CONTENT_URL', $contentUrl);

        // Plugin directory
        define('WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins');
        define('WP_PLUGIN_URL', WP_CONTENT_URL . '/plugins');

        // "Must Use" plugin directory
        define('WPMU_PLUGIN_DIR', WP_CONTENT_DIR . '/mu-plugins');
        define('WPMU_PLUGIN_URL', WP_CONTENT_URL . '/mu-plugins');

        // Absolute path to the WordPress core files
        if (!defined('ABSPATH')) {
            define('ABSPATH', $this->absPath . '/');
        }
    }

    /**
     * Configure WordPress database constants.
     *
     * @return void
     */
    private function applyDatabaseConfig(): void
    {
        global $table_prefix;
        $table_prefix = Env::getString('DATABASE_PREFIX', 'wp_');

        define('DB_CHARSET', Env::getString('DATABASE_CHARSET', 'utf8mb4'));
        define('DB_COLLATE', Env::getString('DATABASE_COLLATE', ''));

        // Configure the database using the given URL
        $databaseResource = Env::getURL('DATABASE_URL');
        if ($databaseResource === null) {
            throw new InvalidEnvVarException(
                'Env var DATABASE_URL is required'
            );
        }
        if ($databaseResource['scheme'] !== 'mysql') {
            throw new UnsupportedSchemeException(
                "Unsupported database scheme '{$databaseResource['scheme']}'"
            );
        }

        $hostString = $databaseResource['port']
            ? $databaseResource['hostName'] . ':' . $databaseResource['port']
            : $databaseResource['hostName'];

        define('DB_HOST', $hostString);
        define('DB_NAME', substr($databaseResource['path'], 1));
        define('DB_USER', $databaseResource['userName']);
        define('DB_PASSWORD', $databaseResource['password'] ?: null);

        $sslMode = strtolower($databaseResource['query']['ssl-mode'] ?? '');
        if ($sslMode === 'required') {
            define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
        }
    }

    /**
     * Configure WordPress keys and salts constants.
     *
     * @return void
     */
    private function applySaltsConfig(): void
    {
        // Default salt drawn from the default wp-config.php file
        $d = 'put your unique phrase here';
        define('AUTH_KEY',         Env::getString('SALT_AUTH_KEY', $d));
        define('SECURE_AUTH_KEY',  Env::getString('SALT_SECURE_AUTH_KEY', $d));
        define('LOGGED_IN_KEY',    Env::getString('SALT_LOGGED_IN_KEY', $d));
        define('NONCE_KEY',        Env::getString('SALT_NONCE_KEY', $d));
        define('AUTH_SALT',        Env::getString('SALT_AUTH_SALT', $d));
        define('SECURE_AUTH_SALT', Env::getString('SALT_SECURE_AUTH_SALT', $d));
        define('LOGGED_IN_SALT',   Env::getString('SALT_LOGGED_IN_SALT', $d));
        define('NONCE_SALT',       Env::getString('SALT_NONCE_SALT', $d));
    }

    /**
     * Configure WordPress environment and settings constants.
     *
     * @return void
     */
    private function applyEnvironmentConfig(): void
    {
        // Environment type
        // See https://developer.wordpress.org/reference/functions/wp_get_environment_type/
        $environmentTypes = ['local', 'development', 'staging', 'production'];
        $environmentType = Env::getString('WP_ENVIRONMENT_TYPE', 'production');
        if (in_array($environmentType, $environmentTypes, true)) {
            define('WP_ENVIRONMENT_TYPE', $environmentType);
        }

        // Debug flags
        $isDebug = Env::getBool('WP_DEBUG', false);
        define('WP_DEBUG', $isDebug);
        define('SCRIPT_DEBUG', $isDebug);
        define('SAVEQUERIES', $isDebug);

        // Settings
        define('WP_AUTO_UPDATE_CORE', false);
        define('AUTOMATIC_UPDATER_DISABLED', true);
        define('DISALLOW_FILE_EDIT', true);
        define('DISALLOW_FILE_MODS', true);
        define('FS_METHOD', 'direct');
        define('ALLOW_UNFILTERED_UPLOADS', false);

        define('DISABLE_WP_CRON', !Env::getBool('WP_CRON', true));
        define('WP_POST_REVISIONS', Env::getInt('WP_POST_REVISIONS', -1));
        define('WP_ALLOW_REPAIR', Env::getBool('WP_ALLOW_REPAIR'), false);
        define('WP_CACHE', Env::getBool('WP_CACHE', false));

        $defaultTheme = Env::getString('WP_DEFAULT_THEME', '');
        if ($defaultTheme !== '') {
            define('WP_DEFAULT_THEME', $defaultTheme);
        }

        // Multisite settings
        if (Env::getBool('MULTISITE_ALLOW', false)) {
            define('WP_ALLOW_MULTISITE', true);
            if (Env::getBool('MULTISITE_ENABLE', false)) {
                define('MULTISITE', true);
                $msType = Env::getString('MULTISITE_TYPE', 'subdirectories');
                define('SUBDOMAIN_INSTALL', $msType === 'subdomains');
                define('DOMAIN_CURRENT_SITE', Env::getString('MULTISITE_DOMAIN'));
                define('PATH_CURRENT_SITE', Env::getString('MULTISITE_PATH'));
                define('SITE_ID_CURRENT_SITE', 1);
                define('BLOG_ID_CURRENT_SITE', 1);
            }
        }
    }

    /**
     * Configure plugin constants.
     *
     * @return void
     */
    private function applyPluginConfig(): void
    {
        // ACF PRO
        $acfProLicense = Env::getString('PLUGIN_ACF_PRO_LICENSE', '');
        if ($acfProLicense !== '') {
            define('ACF_PRO_LICENSE', $acfProLicense);
        }

        // WP Super Cache
        define('WPCACHEHOME', WP_PLUGIN_DIR . '/wp-super-cache/');
    }
}
