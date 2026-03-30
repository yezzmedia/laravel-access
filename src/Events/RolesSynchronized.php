<?php

declare(strict_types=1);

namespace YezzMedia\Access\Events;

/**
 * Marks the completion of one explicit role synchronization run.
 */
final readonly class RolesSynchronized
{
    /**
     * @param  array<int, string>  $roleNames
     */
    public function __construct(
        public array $roleNames,
        public int $createdCount,
        public int $updatedCount,
        public int $unchangedCount,
    ) {}
}
