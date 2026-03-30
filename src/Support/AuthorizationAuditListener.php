<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use YezzMedia\Access\Contracts\AuthorizationAuditWriter;
use YezzMedia\Access\Events\PermissionsSynchronized;
use YezzMedia\Access\Events\RolesSynchronized;
use YezzMedia\Access\Events\UserRoleAssigned;
use YezzMedia\Access\Events\UserRoleRemoved;

/**
 * Bridges access-owned runtime events into normalized audit writes.
 */
final readonly class AuthorizationAuditListener
{
    public function __construct(
        private AuthorizationAuditWriter $writer,
    ) {}

    public function handlePermissionsSynchronized(PermissionsSynchronized $event): void
    {
        $this->writer->write('access.permissions.synchronized', [
            'package_names' => $event->packageNames,
            'created_count' => $event->createdCount,
            'updated_count' => $event->updatedCount,
            'unchanged_count' => $event->unchangedCount,
            'removed_count' => $event->removedCount,
        ]);
    }

    public function handleRolesSynchronized(RolesSynchronized $event): void
    {
        $this->writer->write('access.roles.synchronized', [
            'role_names' => $event->roleNames,
            'created_count' => $event->createdCount,
            'updated_count' => $event->updatedCount,
            'unchanged_count' => $event->unchangedCount,
        ]);
    }

    public function handleUserRoleAssigned(UserRoleAssigned $event): void
    {
        $this->writer->write('access.user_role.assigned', [
            'user_id' => $event->userId,
            'role_name' => $event->roleName,
            'actor_id' => $event->actorId,
            'guard_name' => $event->guardName,
        ]);
    }

    public function handleUserRoleRemoved(UserRoleRemoved $event): void
    {
        $this->writer->write('access.user_role.removed', [
            'user_id' => $event->userId,
            'role_name' => $event->roleName,
            'actor_id' => $event->actorId,
            'guard_name' => $event->guardName,
        ]);
    }
}
