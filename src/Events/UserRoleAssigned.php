<?php

declare(strict_types=1);

namespace YezzMedia\Access\Events;

/**
 * Marks one successful user-role assignment in the access runtime.
 */
final readonly class UserRoleAssigned
{
    public function __construct(
        public int|string $userId,
        public string $roleName,
        public int|string|null $actorId,
        public ?string $guardName,
    ) {}
}
