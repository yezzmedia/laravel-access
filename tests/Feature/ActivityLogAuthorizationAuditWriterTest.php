<?php

declare(strict_types=1);

use Spatie\Activitylog\Models\Activity;
use YezzMedia\Access\Contracts\AuthorizationAuditWriter;
use YezzMedia\Access\Support\ActivityLogAuthorizationAuditWriter;

it('persists normalized authorization audit records through activitylog', function (): void {
    if (! class_exists(Activity::class)) {
        $this->markTestSkipped('spatie/laravel-activitylog is not installed in the package environment.');
    }

    config()->set('access.audit.driver', 'activitylog');
    app()->forgetInstance(AuthorizationAuditWriter::class);

    $writer = app(AuthorizationAuditWriter::class);

    expect($writer)->toBeInstanceOf(ActivityLogAuthorizationAuditWriter::class);

    $writer->write('access.user_role.assigned', [
        'user_id' => 42,
        'role_name' => 'content_editor',
        'actor_id' => 7,
        'guard_name' => 'web',
    ]);

    $activity = Activity::query()->latest('id')->first();

    expect($activity)->not->toBeNull()
        ->and($activity?->log_name)->toBe('access')
        ->and($activity?->event)->toBe('access.user_role.assigned')
        ->and($activity?->description)->toBe('access.user_role.assigned')
        ->and($activity?->getProperty('user_id'))->toBe(42)
        ->and($activity?->getProperty('role_name'))->toBe('content_editor')
        ->and($activity?->getProperty('actor_id'))->toBe(7)
        ->and($activity?->getProperty('guard_name'))->toBe('web');
});
