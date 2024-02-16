<?php

declare(strict_types=1);

namespace EnvPress\Layer;

/**
 * Configure security related aspects of a WordPress instance.
 */
class SecurityLayer implements LayerInterface
{
    /**
     * Create a new SecurityLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new SecurityLayer instance.
     *
     * @return SecurityLayer
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
        // Hide the exact WordPress version to harden the instance against
        // automated attacks targeting specific versions
        add_filter('the_generator', '__return_empty_string');
    }
}
