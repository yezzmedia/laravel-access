<?php

declare(strict_types=1);

use Tests\Fixtures\TestUser;
use YezzMedia\Access\Support\PermissionCacheManager;

it('forgets the global permission map cache entry', function (): void {
    $manager = app(PermissionCacheManager::class);

    cache()->put($manager->allKey(), ['content.pages.publish'], 600);

    $manager->forgetAll();

    expect(cache()->has($manager->allKey()))->toBeFalse();
});

it('forgets a role-specific permission map cache entry', function (): void {
    $manager = app(PermissionCacheManager::class);

    cache()->put($manager->roleKey('content_editor'), ['content.pages.publish'], 600);

    $manager->forgetRole('content_editor');

    expect(cache()->has($manager->roleKey('content_editor')))->toBeFalse();
});

it('forgets a user-specific permission map cache entry', function (): void {
    $manager = app(PermissionCacheManager::class);
    $user = TestUser::query()->create(['name' => 'Editor']);

    cache()->put($manager->userKey($user), ['content.pages.publish'], 600);

    $manager->forgetUser($user);

    expect(cache()->has($manager->userKey($user)))->toBeFalse();
});
