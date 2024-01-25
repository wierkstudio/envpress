<?php

declare(strict_types=1);

namespace EnvPress\Layer;

interface LayerInterface
{
    /**
     * Return the name of a WordPress hook from which this layer should be
     * applied. If null is returned, the layer should be applied immediately.
     *
     * @return string|null
     */
    public function getHookName(): string|null;

    /**
     * Decide on whether this layer should be applied based on the current
     * environment.
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Apply the configuration of this layer.
     *
     * @return void
     */
    public function apply(): void;
}
