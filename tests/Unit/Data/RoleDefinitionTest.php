<?php

declare(strict_types=1);

use YezzMedia\Access\Data\RoleDefinition;

it('stores the approved role definition fields', function (): void {
    $role = new RoleDefinition(
        name: 'content_manager',
        label: 'Content manager',
        description: 'Can manage content publishing operations.',
        permissionNames: ['content.pages.publish', 'content.pages.unpublish'],
    );

    expect($role->name)->toBe('content_manager')
        ->and($role->label)->toBe('Content manager')
        ->and($role->description)->toBe('Can manage content publishing operations.')
        ->and($role->permissionNames)->toBe(['content.pages.publish', 'content.pages.unpublish']);
});
