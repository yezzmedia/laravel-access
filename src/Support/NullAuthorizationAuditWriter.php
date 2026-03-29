<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use YezzMedia\Access\Contracts\AuthorizationAuditWriter;

/**
 * Keeps access usable when no audit persistence driver is configured.
 */
final class NullAuthorizationAuditWriter implements AuthorizationAuditWriter
{
    public function write(string $eventKey, array $context = []): void {}
}
