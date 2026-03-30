<?php

declare(strict_types=1);

namespace YezzMedia\Access\Data;

/**
 * Describes one reusable role preset for explicit access seeding flows.
 */
final readonly class RoleDefinition
{
    /**
     * @param  array<int, string>  $permissionNames
     */
    public function __construct(
        public string $name,
        public string $label,
        public string $description,
        public array $permissionNames,
    ) {}
}
