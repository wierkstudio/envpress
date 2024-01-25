<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Util\Env;
use WP_Error;

/**
 * Configure outgoing emails (SMTP, …).
 */
class MailLayer implements LayerInterface
{
    /**
     * Create a new MailLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new MailLayer instance.
     *
     * @return MailLayer
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
        // Configure from email
        add_filter('wp_mail_from', function (string $email): string {
            return Env::getString('SMTP_FROM_EMAIL', $email);
        });

        // Configure from name
        add_filter('wp_mail_from_name', function (string $name): string {
            return Env::getString('SMTP_FROM_NAME', $name);
        });

        // Configure SMTP
        if (Env::getString('SMTP_HOSTNAME', '') !== '') {
            add_action('phpmailer_init', function ($mailer): void {
                $mailer->isSMTP();
                $mailer->Host       = Env::getString('SMTP_HOSTNAME');
                $mailer->Port       = Env::getInt('SMTP_PORT', 587);
                $mailer->Username   = Env::getString('SMTP_USERNAME', '');
                $mailer->Password   = Env::getString('SMTP_PASSWORD', '');
                $mailer->SMTPAuth   = Env::getBool('SMTP_AUTH', true);
                $mailer->SMTPSecure = Env::getString('SMTP_ENCRYPTION', 'tls');
            });
        }

        // Configure failed mail logging
        add_action('wp_mail_failed', function (WP_Error $error): void {
            error_log('wp_mail_failed: ' . $error->get_error_message());
        });
    }
}
