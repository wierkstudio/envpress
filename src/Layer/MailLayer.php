<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Exception\UnsupportedSchemeException;
use EnvPress\Util\Env;
use PHPMailer\PHPMailer\PHPMailer;
use WP_Error;

/**
 * Configure outgoing emails (SMTP, …).
 */
class MailLayer implements LayerInterface
{
    /**
     * Priority of the `wp_mail_from` filter hook enforcing a specific from
     * mail address provided by the mailer resource URL.
     */
    const MAILER_FROM_FILTER_PRIORITY = 10_001;

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
            return Env::getString('MAILER_FROM_ADDRESS', $email);
        });

        // Configure from name
        add_filter('wp_mail_from_name', function (string $name): string {
            return Env::getString('MAILER_FROM_NAME', $name);
        });

        // Configure mailer
        $mailerResource = Env::getURL('MAILER_URL');
        if ($mailerResource !== null) {
            $this->attachMailerResource($mailerResource);
        }

        // Configure failed mail logging
        add_action('wp_mail_failed', function (WP_Error $error): void {
            error_log('wp_mail_failed: ' . $error->get_error_message());
        });
    }

    /**
     * Configure PHPMailer to use the given mailer resource.
     *
     * @param array $resource Mailer resource URL components
     *
     * @return void
     */
    private function attachMailerResource(array $resource): void
    {
        if ($resource['scheme'] !== 'smtp') {
            throw new UnsupportedSchemeException(
                "Unsupported mailer scheme '{$resource['scheme']}'"
            );
        }

        add_action('phpmailer_init', function ($mailer) use ($resource): void {
            $mailer->isSMTP();

            // Encryption
            $useTLS = ($resource['query']['encryption'] ?? '') !== 'ssl';
            $mailer->SMTPSecure = $useTLS
                ? PHPMailer::ENCRYPTION_STARTTLS
                : PHPMailer::ENCRYPTION_SMTPS;

            // Hostname and port
            $mailer->Host = $resource['hostName'];
            $mailer->Port = $resource['port'] ?: ($useTLS ? 587 : 465);

            // Authentication
            if ($resource['userName'] !== '') {
                $mailer->Username = $resource['userName'];
                $mailer->Password = $resource['password'];
                $mailer->SMTPAuth = true;
            }
        });

        if (!empty($resource['query']['from'])) {
            // Force a from address for this resource
            $fromAddress = $resource['query']['from'];
            add_filter('wp_mail_from', function () use ($fromAddress): string {
                return $fromAddress;
            }, self::MAILER_FROM_FILTER_PRIORITY);
        }
    }
}
