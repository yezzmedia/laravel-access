<?php

declare(strict_types=1);

use YezzMedia\Access\Contracts\AuthorizationAuditWriter;
use YezzMedia\Access\Events\PermissionsSynchronized;
use YezzMedia\Access\Events\RolesSynchronized;
use YezzMedia\Access\Events\UserRoleAssigned;
use YezzMedia\Access\Events\UserRoleRemoved;

it('writes normalized audit records for registered access runtime events', function (): void {
    $writer = new class implements AuthorizationAuditWriter
    {
        /**
         * @var array<int, array{event_key: string, context: array<string, mixed>}>
         */
        public array $writes = [];

        public function write(string $eventKey, array $context = []): void
        {
            $this->writes[] = [
                'event_key' => $eventKey,
                'context' => $context,
            ];
        }
    };

    app()->forgetInstance(AuthorizationAuditWriter::class);
    app()->instance(AuthorizationAuditWriter::class, $writer);

    event(new PermissionsSynchronized(
        packageNames: ['yezzmedia/laravel-content'],
        createdCount: 2,
        updatedCount: 1,
        unchangedCount: 3,
        removedCount: 0,
    ));

    event(new RolesSynchronized(
        roleNames: ['content_editor'],
        createdCount: 1,
        updatedCount: 0,
        unchangedCount: 1,
    ));

    event(new UserRoleAssigned(
        userId: 10,
        roleName: 'content_editor',
        actorId: 3,
        guardName: 'web',
    ));

    event(new UserRoleRemoved(
        userId: 10,
        roleName: 'content_editor',
        actorId: 3,
        guardName: 'web',
    ));

    expect($writer->writes)->toBe([
        [
            'event_key' => 'access.permissions.synchronized',
            'context' => [
                'package_names' => ['yezzmedia/laravel-content'],
                'created_count' => 2,
                'updated_count' => 1,
                'unchanged_count' => 3,
                'removed_count' => 0,
            ],
        ],
        [
            'event_key' => 'access.roles.synchronized',
            'context' => [
                'role_names' => ['content_editor'],
                'created_count' => 1,
                'updated_count' => 0,
                'unchanged_count' => 1,
            ],
        ],
        [
            'event_key' => 'access.user_role.assigned',
            'context' => [
                'user_id' => 10,
                'role_name' => 'content_editor',
                'actor_id' => 3,
                'guard_name' => 'web',
            ],
        ],
        [
            'event_key' => 'access.user_role.removed',
            'context' => [
                'user_id' => 10,
                'role_name' => 'content_editor',
                'actor_id' => 3,
                'guard_name' => 'web',
            ],
        ],
    ]);
});
