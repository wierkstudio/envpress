<?php

declare(strict_types=1);

namespace EnvPress\Layer;

use EnvPress\Exception\InvalidEnvVarException;
use EnvPress\Util\Env;

/**
 * Apply role and capability changes to WordPress.
 *
 * Must be applied after the MultisiteLayer.
 */
class RolesLayer implements LayerInterface
{
    /**
     * Name of the option storing the last applied version of the roles patch.
     */
    const ROLES_PATCH_VERSION_OPTION = 'envpress_roles_patch_version';

    const ENV_VAR_KEY = 'WP_ROLES_PATCH';

    /**
     * Create a new RolesLayer instance.
     *
     * @return void
     */
    private function __construct()
    {
        //
    }

    /**
     * Create a new RolesLayer instance.
     *
     * @return RolesLayer
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
        return 'init';
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
        $rolesPatch = Env::getString(self::ENV_VAR_KEY, '');
        if (empty($rolesPatch)) {
            return;
        }

        // Use the SHA1 hash of the env var value as a version
        $version = sha1($rolesPatch);

        if (!is_multisite()) {
            $this->applyRolesPatch($rolesPatch, $version);
        } else {
            $sites = get_sites([
                'fields' => 'ids',
                // The magic -1 does not appear to work here
                'number' => 1024,
            ]);
            foreach ($sites as $blogId) {
                switch_to_blog($blogId);
                $this->applyRolesPatch($rolesPatch, $version);
                restore_current_blog();
            }
        }
    }

    /**
     * Apply the configuration of this layer to the current site.
     *
     * @return void
     */
    private function applyRolesPatch(string $rolesPatch, string $version): void
    {
        // Note that `get_option` and `update_option` are site-specific
        $appliedVersion = get_option(self::ROLES_PATCH_VERSION_OPTION, '');
        if ($appliedVersion !== $version) {
            $rolePatches = @json_decode($rolesPatch, true);

            if (
                json_last_error() !== JSON_ERROR_NONE ||
                !is_array($rolePatches)
            ) {
                throw new InvalidEnvVarException(
                    'Env var ' . self::ENV_VAR_KEY . ' contains unexpected JSON'
                );
            }

            foreach ($rolePatches as $name => $rolePatch) {
                $this->applyRolePatch($name, $rolePatch);
            }

            // Autoload the roles patch version for improved performance
            update_option(self::ROLES_PATCH_VERSION_OPTION, $version, true);
        }
    }

    /**
     * Apply a single role patch.
     *
     * @param string $name Role name
     * @param array $patch Set of role patch instructions
     *
     * @return void
     */
    private function applyRolePatch(string $name, array $patch): void
    {
        $removeRole = ($patch['remove_role'] ?? false) === true;

        $addCaps = $this->parseCaps($patch['add_cap'] ?? []);
        $removeCaps = $this->parseCaps($patch['remove_cap'] ?? []);
        $denyCaps = $this->parseCaps($patch['deny_cap'] ?? []);

        // Retrieve role by name
        $role = get_role($name);
        if ($role === null && $removeRole) {
            // Role does not exist and should be removed. No changes needed.
            return;
        }

        if ($role === null) {
            $displayName =
                !empty($patch['display_name']) &&
                is_string($patch['display_name'])
                    ? $patch['display_name']
                    : $name;

            // Compose initial capabilities
            $capabilities = array_merge(
                // Order matters
                array_fill_keys($addCaps, true),
                array_fill_keys($denyCaps, false)
            );

            // Create role with capabilities
            $role = add_role($name, $displayName, $capabilities);
        } else {
            // Add granted capabilities
            foreach ($addCaps as $cap) {
                $role->add_cap($cap, true);
            }

            // Remove capabilities
            foreach ($removeCaps as $cap) {
                $role->remove_cap($cap);
            }

            // Add explicitly denied capabilities
            foreach ($denyCaps as $cap) {
                $role->add_cap($cap, false);
            }
        }
    }

    private function parseCaps(string|array $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (is_array($value) && array_is_list($value)) {
            $valid = true;
            foreach ($value as $cap) {
                if (!is_string($cap) || empty($cap)) {
                    $valid = false;
                    break;
                }
            }
            if ($valid) {
                return $value;
            }
        }

        throw new InvalidEnvVarException(
            'Env var ' . self::ENV_VAR_KEY . ' contains invalid capabilities'
        );
    }
}
