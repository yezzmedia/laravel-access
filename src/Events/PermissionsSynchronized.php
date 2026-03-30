<?php

declare(strict_types=1);

namespace YezzMedia\Access\Events;

/**
 * Marks the completion of one deterministic permission synchronization run.
 */
final readonly class PermissionsSynchronized
{
    /**
     * @param  array<int, string>  $packageNames
     */
    public function __construct(
        public array $packageNames,
        public int $createdCount,
        public int $updatedCount,
        public int $unchangedCount,
        public int $removedCount = 0,
    ) {}
}
