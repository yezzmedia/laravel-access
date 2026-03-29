<?php

declare(strict_types=1);

namespace YezzMedia\Access\Contracts;

interface AuthorizationAuditWriter
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function write(string $eventKey, array $context = []): void;
}
