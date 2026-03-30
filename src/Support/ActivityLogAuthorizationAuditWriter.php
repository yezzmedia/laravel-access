<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Spatie\Activitylog\Support\ActivityLogger;
use YezzMedia\Access\Contracts\AuthorizationAuditWriter;

/**
 * Persists normalized access audit records through activitylog when enabled.
 */
final class ActivityLogAuthorizationAuditWriter implements AuthorizationAuditWriter
{
    public function __construct(
        private readonly ActivityLogger $logger,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function write(string $eventKey, array $context = []): void
    {
        $this->logger
            ->useLog('access')
            ->event($eventKey)
            ->withProperties($context)
            ->log($eventKey);
    }
}
