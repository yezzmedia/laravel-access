<?php

declare(strict_types=1);

use YezzMedia\Access\Tests\Concerns\InteractsWithPermissionSync;
use YezzMedia\Access\Tests\Concerns\InteractsWithRoles;
use YezzMedia\Foundation\Data\PermissionDefinition;

it('builds role fixtures and synchronizes them through the real role manager path', function (): void {
    $helper = new class
    {
        use InteractsWithPermissionSync;
        use InteractsWithRoles;
    };

    $helper->registerPermissionDefinitions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    $helper->syncPermissions();

    $roles = [
        $helper->makeRoleDefinition('content_editor', ['content.pages.publish', 'content.pages.archive']),
        $helper->makeRoleDefinition('content_archivist', ['content.pages.archive']),
    ];

    $helper->syncRoleDefinitions($roles);

    $helper->assertRoleHasPermissions('content_editor', [
        'content.pages.archive',
        'content.pages.publish',
    ]);
    $helper->assertRoleHasPermissions('content_archivist', [
        'content.pages.archive',
    ]);
});

it('creates readable default role fixture metadata through the role helper', function (): void {
    $helper = new class
    {
        use InteractsWithRoles;
    };

    $role = $helper->makeRoleDefinition('platform_admin', ['content.pages.publish']);

    expect($role->label)->toBe('Platform Admin')
        ->and($role->description)->toBe('Test role for [platform_admin].')
        ->and($role->permissionNames)->toBe(['content.pages.publish']);
});
