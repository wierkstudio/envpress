
# EnvPress

A PHP package streamlining the configuration of modern and secure WordPress instances using environment variables.

## Motivation

The standard setup of WordPress involves maintaining a wp-config.php file for setting basic configuration options, such as paths, the database connection, and security salts. Except for the actual configuration values, the source code of this file is repeated for each instance with little to no variation. Other common configurations, such as SMTP server credentials and disabling XML-RPC, must be handled separately, far away from wp-config.php, in a custom (child) theme or using multiple third-party plugins.

This package is designed to simplify the configuration process and lessen the maintenance workload for the majority of WordPress instances. It relies on a standard set of environment variables (see list below), rather than boilerplate PHP code, to configure an instance.

## Key Features

- Designed for [Composer](https://getcomposer.org/) based WordPress setups (e.g. [Bedrock](https://roots.io/bedrock/))
- No configuration as constants in wp-config.php
- Load environment variables from .env files with [vlucas/phpdotenv](https://github.com/vlucas/phpdotenv)
- Gather facts from trusted proxies (e.g. Load Balancers)
- Configure [Multisite Networks](https://wordpress.org/documentation/article/create-a-network/) and [fix paths](https://core.trac.wordpress.org/ticket/36507) when installing WordPress in a custom subdirectory
- Outgoing mail configuration (SMTP server credentials, sender details)
- Disable file modifications by WordPress and its built-in file editor
- Harden WordPress: Disable XML-RPC and pingbacks, hide exact version
- Disable comments and related features with a flag

## Getting Started

1.  Install the package via Composer:

    ```bash
    composer require wierk/envpress
    ```

2.  Set up environment variables:

    - Configure environment variables in the web server or PHP config (recommended for production)
    - Add environment variables to a .env file in the root of the project (common for development)

3.  Update wp-config.php to the following:

    ```php
    <?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    \EnvPress\EnvPress::createWithDefaults(__DIR__)->bootstrap();
    require_once ABSPATH . 'wp-settings.php';
    ```

    If the [Bedrock folder structure](https://roots.io/bedrock/docs/folder-structure/) is used, it can be configured using the following snippet:

    ```php
    <?php
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    \EnvPress\EnvPress::createWithBedrockDefaults(__DIR__)->bootstrap();
    require_once ABSPATH . 'wp-settings.php';
    ```

## Environment Variables

EnvPress configures a WordPress instance based on the following environment variables that can be set in the PHP pool configuration (recommended) or via a `.env` file:

| Environment variable | Comments | Default |
| ----------- | ----------- | ------- |
| `WP_HOME` | Root URL of the site | Required |
| `WP_SITEURL` | URL of the WordPress folder | Required |
| `WP_ENVIRONMENT_TYPE` | Environment type | `production` |
| `WP_DEBUG` | Flag to enable debugging mode | `false` |
| `WP_CACHE` | Flag to enable cache | `false` |
| `WP_CRON` | Flag to enable WP Cron based on page load | `true` |
| `WP_DEFAULT_THEME` | Default WordPress theme name | No change |
| `WP_POST_REVISIONS` | Number of [post revisions](https://wordpress.org/documentation/article/revisions/) (-1, 0, 1, 2, …) | `0` |
| `MULTISITE_ALLOW` | Flag to allow a [multisite network](https://wordpress.org/documentation/article/create-a-network/) | `false` |
| `MULTISITE_ENABLE` | Flag to enable a multisite network, once installed | `false` |
| `MULTISITE_TYPE` | Either `subdomains` or `subdirectories` | `subdirectories` |
| `MULTISITE_DOMAIN` | Value of `DOMAIN_CURRENT_SITE` | Required for MS |
| `MULTISITE_PATH` | Value of `PATH_CURRENT_SITE` | Required for MS |
| `DB_HOSTNAME` | Hostname of the MySQL server | `127.0.0.1` |
| `DB_PORT` | Port of the MySQL server | `3306` |
| `DB_USERNAME` | MySQL user name | Required |
| `DB_PASSWORD` | MySQL password | Required |
| `DB_DATABASE` | MySQL database name | Required |
| `DB_CHARSET` | MySQL database charset | `utf8mb4` |
| `DB_COLLATE` | MySQL database collate | Empty |
| `DB_PREFIX` | Database table prefix | `wp_` |
| `SALT_AUTH_KEY` | Cryptographically strong and random key | `put your uniqu…` |
| `SALT_SECURE_AUTH_KEY` | Cryptographically strong and random key | `put your uniqu…` |
| `SALT_LOGGED_IN_KEY` | Cryptographically strong and random key | `put your uniqu…` |
| `SALT_NONCE_KEY` | Cryptographically strong and random key | `put your uniqu…` |
| `SALT_AUTH_SALT` | Cryptographically strong and random key | `put your uniqu…` |
| `SALT_SECURE_AUTH_SALT` | Cryptographically strong and random key | `put your uniqu…` |
| `SALT_LOGGED_IN_SALT` | Cryptographically strong and random key | `put your uniqu…` |
| `SALT_NONCE_SALT` | Cryptographically strong and random key | `put your uniqu…` |
| `SMTP_HOSTNAME` | Hostname of the SMTP server | Empty (disabled) |
| `SMTP_PORT` | Port of the SMTP server | `587` |
| `SMTP_USERNAME` | SMTP user name | Empty |
| `SMTP_PASSWORD` | SMTP password | Empty |
| `SMTP_AUTH` | Whether to authenticate to the SMTP server | `true` |
| `SMTP_ENCRYPTION` | SMTP encryption | `tls` |
| `SMTP_FROM_EMAIL` | From email address for outgoing mail | No change |
| `SMTP_FROM_NAME` | From name for outgoing mail | No change |
| `DISCUSSION_COMMENTS` | Flag to enable comments and related features | `true` |
| `MARKETING_TRACKING_ROLES` | CSV of [user role slugs](https://wordpress.org/documentation/article/roles-and-capabilities/) tracking is enabled for | `guest` |
| `MARKETING_FATHOM` | [Fathom Analytics](https://usefathom.com/) Site id | Empty |
| `MARKETING_GTM` | [Google Tag Manager](https://marketingplatform.google.com/about/tag-manager/) Container id | Empty |
| `ADMIN_SUPPORT_NAME` | Name of person/company offering support | Empty |
| `ADMIN_SUPPORT_URL` | URL of person/company offering support | Empty |
| `ADMIN_SHOW_ENV` | Flag to show the environment type in admin | `false` |
| `PLUGIN_ACF_PRO_LICENSE` | License key for [ACF PRO](https://www.advancedcustomfields.com/pro/) | Empty (disabled) |
| `ENVPRESS_TRUSTED_PROXIES` | CSV of trusted proxy addresses | Empty (disabled) |

## Credits

Created by [Wierk](https://wierk.lu/) and [contributors](https://github.com/wierkstudio/envpress/graphs/contributors). Released under the [MIT license](./LICENSE.txt).
