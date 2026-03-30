<?php

declare(strict_types=1);

use YezzMedia\Access\Doctor\SuperAdminConfiguredCheck;

it('skips when super-admin bootstrap is disabled', function (): void {
    config()->set('access.super_admin.enabled', false);
    config()->set('access.super_admin.role_name', null);

    $result = app(SuperAdminConfiguredCheck::class)->run();

    expect($result->key)->toBe('super_admin_configured')
        ->and($result->package)->toBe('yezzmedia/laravel-access')
        ->and($result->status)->toBe('skipped')
        ->and($result->isBlocking)->toBeFalse();
});

it('fails when super-admin bootstrap is enabled without a valid role name', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', '   ');

    $result = app(SuperAdminConfiguredCheck::class)->run();

    expect($result->status)->toBe('failed')
        ->and($result->isBlocking)->toBeTrue()
        ->and($result->context)->toMatchArray([
            'exception' => InvalidArgumentException::class,
        ]);
});

it('passes when super-admin bootstrap is enabled with a valid role name', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', ' super_admin ');

    $result = app(SuperAdminConfiguredCheck::class)->run();

    expect($result->status)->toBe('passed')
        ->and($result->isBlocking)->toBeFalse()
        ->and($result->context)->toBe([
            'role_name' => 'super_admin',
        ]);
});
