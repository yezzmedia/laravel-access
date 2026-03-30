<?php

declare(strict_types=1);

namespace YezzMedia\Access\Events;

/**
 * Marks one successful user-role removal in the access runtime.
 */
final readonly class UserRoleRemoved
{
    public function __construct(
        public int|string $userId,
        public string $roleName,
        public int|string|null $actorId,
        public ?string $guardName,
    ) {}
}
