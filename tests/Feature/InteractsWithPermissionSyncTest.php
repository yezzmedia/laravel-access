<?php

declare(strict_types=1);

use YezzMedia\Access\Tests\Concerns\InteractsWithPermissionSync;
use YezzMedia\Foundation\Data\PermissionDefinition;

it('registers permission definitions and runs the real sync path through the testing helper', function (): void {
    $helper = new class
    {
        use InteractsWithPermissionSync;
    };

    $helper->registerPermissionDefinitions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
        new PermissionDefinition('content.pages.archive', 'yezzmedia/laravel-content', 'Archive pages'),
    ]);

    $result = $helper->syncPermissions();

    expect($result->packageNames)->toBe(['yezzmedia/laravel-content'])
        ->and($result->createdCount)->toBe(2);

    $helper->assertSyncedPermissions([
        'content.pages.archive',
        'content.pages.publish',
    ]);
});

it('can synchronize one registered package through the permission sync helper', function (): void {
    $helper = new class
    {
        use InteractsWithPermissionSync;
    };

    $helper->registerPermissionDefinitions('yezzmedia/laravel-content', [
        new PermissionDefinition('content.pages.publish', 'yezzmedia/laravel-content', 'Publish pages'),
    ]);
    $helper->registerPermissionDefinitions('yezzmedia/laravel-media', [
        new PermissionDefinition('media.assets.delete', 'yezzmedia/laravel-media', 'Delete assets'),
    ]);

    $result = $helper->syncPermissionsForPackage('yezzmedia/laravel-media');

    expect($result->packageNames)->toBe(['yezzmedia/laravel-media'])
        ->and($result->createdCount)->toBe(1);

    $helper->assertSyncedPermissions(['media.assets.delete']);
});
