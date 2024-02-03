
# EnvPress

A PHP package streamlining the configuration of modern and secure WordPress instances using a standard set of environment variables.

## Key Features

- Designed for [Composer](https://getcomposer.org/) based WordPress setups (e.g. [Bedrock](https://roots.io/bedrock/))
- Static wp-config.php
- Load environment variables from .env files with [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)
- Gather facts from trusted proxies (e.g. Load Balancers)
- Attach [backing services](https://www.12factor.net/backing-services) using URLs (e.g. MySQL, SMTP)
- Configure [Multisite Networks](https://wordpress.org/documentation/article/create-a-network/) using environment variables
- Use feature flags to disable native WordPress features (e.g. comments, oEmbed)
- Harden WordPress: Disable file modifications, XML-RPC, and pingbacks, hide exact version

## Motivation

The standard setup of WordPress involves maintaining a wp-config.php file for setting basic configuration options, such as paths, the database connection, and security salts. Except for the actual configuration values, the source code of this file is repeated for each instance with little to no variation. Other common configurations, such as SMTP server credentials and disabling XML-RPC, must be handled separately, far away from wp-config.php, in a custom (child) theme or using multiple third-party plugins.

This package is designed to simplify the configuration process and lessen the maintenance workload for the majority of WordPress instances. It relies on a standard set of environment variables (see list below), rather than boilerplate PHP code, to configure an instance.

## Getting Started

1.  Setup Composer based WordPress project:

    The easiest way to do so, is [creating a new Bedrock project](https://roots.io/bedrock/docs/installation/) using Composer:

    ```bash
    composer create-project roots/bedrock
    ```

2.  Install this package via Composer:

    ```bash
    composer require wierk/envpress
    ```

3.  Set up environment variables:

    Configure environment variables in the web server or PHP config (recommended for production) or, alternatively, add them to a file named .env in the root of the project (common for development).

    Minimal set of environment variables to run a WordPress instance:

    ```ini
    WP_HOME      = https://example.com
    WP_SITEURL   = https://example.com/wp
    DATABASE_URL = mysql://username:password@hostname:port/database
    ```

    Set of env vars providing WordPress salts:

    ```ini
    SALT_AUTH_KEY         = put your unique phrase here
    SALT_SECURE_AUTH_KEY  = put your unique phrase here
    SALT_LOGGED_IN_KEY    = put your unique phrase here
    SALT_NONCE_KEY        = put your unique phrase here
    SALT_AUTH_SALT        = put your unique phrase here
    SALT_SECURE_AUTH_SALT = put your unique phrase here
    SALT_LOGGED_IN_SALT   = put your unique phrase here
    SALT_NONCE_SALT       = put your unique phrase here
    ```

4.  Replace the content of wp-config.php with the following:

    ```php
    <?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    \EnvPress\EnvPress::createWithBedrockDefaults(__DIR__)->bootstrap();
    require_once ABSPATH . 'wp-settings.php';
    ```

    Starting from the Bedrock boilerplate, the root config directory may now be removed.

## Environment Variables

EnvPress sets up a WordPress instance using a collection of environment variables, listed in the following table. In cases where an environment variable is absent, the corresponding default value is used. These default values are selected to closely resemble a standard, unmodified WordPress installation to avoid unintentional changes.

| Environment variable | Comments | Default |
| ----------- | ----------- | ------- |
| `WP_HOME` | [URL the WordPress instance can be reached at](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#blog-address-url) | Required |
| `WP_SITEURL` | [URL where WordPress core files reside](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-siteurl) | Required |
| `WP_ENVIRONMENT_TYPE` | [Environment type](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-environment-type) | `production` |
| `WP_DEBUG` | Flag to enable [the reporting of some errors or warnings](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#wp-debug) | `false` |
| `WP_CACHE` | Flag to enable [advanced-cache.php](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#cache) | `false` |
| `WP_CRON` | Flag to enable [WP Cron based on page load](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#alternative-cron) | `true` |
| `WP_DEFAULT_THEME` | Default WordPress theme name | WordPress default |
| `WP_POST_REVISIONS` | Number of [post revisions](https://wordpress.org/documentation/article/revisions/) (-1, 0, 1, 2, …) | `-1` |
| `WP_ALLOW_REPAIR` | Flag to enable [automatic database repair support](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#automatic-database-optimizing) | `false` |
| `MULTISITE_ALLOW` | Flag to allow a [multisite network](https://wordpress.org/documentation/article/create-a-network/) | `false` |
| `MULTISITE_ENABLE` | Flag to enable a multisite network, once installed | `false` |
| `MULTISITE_TYPE` | Either `subdomains` or `subdirectories` | `subdirectories` |
| `MULTISITE_DOMAIN` | Value of `DOMAIN_CURRENT_SITE` | Required for MS |
| `MULTISITE_PATH` | Value of `PATH_CURRENT_SITE` | Required for MS |
| `DATABASE_URL` | MySQL server URL (see below) | Required |
| `DATABASE_CHARSET` | Database [character set](https://wordpress.org/documentation/article/wordpress-glossary/#character-set) | `utf8mb4` |
| `DATABASE_COLLATE` | Database [collation](https://wordpress.org/documentation/article/wordpress-glossary/#collation) | Empty |
| `DATABASE_PREFIX` | Database [table prefix](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix) | `wp_` |
| `MAILER_FROM_ADDRESS` | Sender email address (may be set in `MAILER_URL`) | WordPress default |
| `MAILER_FROM_NAME` | Sender name | WordPress default |
| `MAILER_URL` | SMTP server URL for outgoing mail (see below) | WordPress default |
| `FEATURE_COMMENTS` | Flag to enable comments and related features | `true` |
| `FEATURE_OEMBED` | Flag to enable oEmbed and related features | `true` |
| `ADMIN_SUPPORT_NAME` | Support contact name | Empty |
| `ADMIN_SUPPORT_URL` | Support contact website URL | Empty |
| `ADMIN_DISPLAY_ENV` | Flag to display the environment type in admin | `false` |
| `RELEASE_VERSION` | Display version of the release | Empty |
| `RELEASE_URL` | Website URL of the release | Empty |
| `SALT_AUTH_KEY` | Cryptographically strong and random key | `put your uni…` |
| `SALT_SECURE_AUTH_KEY` | Cryptographically strong and random key | `put your uni…` |
| `SALT_LOGGED_IN_KEY` | Cryptographically strong and random key | `put your uni…` |
| `SALT_NONCE_KEY` | Cryptographically strong and random key | `put your uni…` |
| `SALT_AUTH_SALT` | Cryptographically strong and random key | `put your uni…` |
| `SALT_SECURE_AUTH_SALT` | Cryptographically strong and random key | `put your uni…` |
| `SALT_LOGGED_IN_SALT` | Cryptographically strong and random key | `put your uni…` |
| `SALT_NONCE_SALT` | Cryptographically strong and random key | `put your uni…` |
| `MARKETING_FATHOM` | [Fathom Analytics](https://usefathom.com/) Site id | Empty |
| `MARKETING_GTM` | [Google Tag Manager](https://marketingplatform.google.com/about/tag-manager/) Container id | Empty |
| `PLUGIN_ACF_PRO_LICENSE` | License key for [ACF PRO](https://www.advancedcustomfields.com/pro/) | Empty (disabled) |
| `ENVPRESS_TRUSTED_PROXIES` | CSV of trusted proxy addresses | Empty (disabled) |

## Backing Service URLs

Backing services such as databases, caching systems, or SMTP servers are attached using URLs. These URLs consolidate all the essential connection details, like host name, port, access credentials, and other relevant parameters, into a singular, manageable string.

In URLs, if a user name or password contains special characters (`$&+,/:;=?@`), they must be [URL encoded](https://en.wikipedia.org/wiki/Percent-encoding).

### Database URL/DSN

```ini
DATABASE_URL=mysql://${USER}:${PASS}@${HOST}:${PORT}/${DATABASE}?ssl-mode=REQUIRED
```

Query parameters:

- `ssl-mode` - If set to `REQUIRED`, requires an encrypted connection and fails, if one cannot be established.

### Mailer URL

```ini
MAILER_URL=smtp://${USER}:${PASS}@${HOST}:${PORT}?encryption=tls
```

Query parameters:

- `encryption` - Define the encryption to use on the SMTP connection: `tls` (default) or `ssl`.
- `from` - If present, force the from email address to a specified one, overwriting `MAILER_FROM_ADDRESS`.

## Credits

Created and maintained by [Wierk](https://wierk.lu/) and [contributors](https://github.com/wierkstudio/envpress/graphs/contributors). Released under the [MIT license](./LICENSE.txt).
