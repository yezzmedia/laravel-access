<?php

declare(strict_types=1);

use Spatie\Activitylog\ActivitylogServiceProvider;
use YezzMedia\Access\Doctor\AccessAuditConfiguredCheck;

it('warns when ops audit is active but access audit is not configured', function (): void {
    config()->set('ops.integrations.audit.provider', ActivitylogServiceProvider::class);
    config()->set('access.audit.driver', null);

    $result = app(AccessAuditConfiguredCheck::class)->run();

    expect($result->status)->toBe('warning')
        ->and($result->isBlocking)->toBeFalse()
        ->and($result->message)->toBe('Access audit events are not persisted because access.audit.driver is not configured.');
});

it('passes when access audit persistence is configured', function (): void {
    config()->set('ops.integrations.audit.provider', ActivitylogServiceProvider::class);
    config()->set('access.audit.driver', 'activitylog');

    $result = app(AccessAuditConfiguredCheck::class)->run();

    expect($result->status)->toBe('passed')
        ->and($result->isBlocking)->toBeFalse();
});

it('skips when ops audit is not configured', function (): void {
    config()->set('ops.integrations.audit.provider', null);
    config()->set('access.audit.driver', 'activitylog');

    $result = app(AccessAuditConfiguredCheck::class)->run();

    expect($result->status)->toBe('skipped')
        ->and($result->isBlocking)->toBeFalse();
});
