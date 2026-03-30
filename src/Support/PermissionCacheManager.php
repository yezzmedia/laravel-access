<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository;
use YezzMedia\Foundation\Support\CacheKeyFactory;

/**
 * Owns explicit invalidation for access permission-map cache entries.
 */
final class PermissionCacheManager
{
    public function __construct(
        private readonly Repository $cache,
        private readonly CacheKeyFactory $cacheKeys,
    ) {}

    public function forgetAll(): void
    {
        $this->cache->forget($this->allKey());
    }

    public function forgetRole(string $role): void
    {
        $this->cache->forget($this->roleKey($role));
    }

    public function forgetUser(Authenticatable $user): void
    {
        $this->cache->forget($this->userKey($user));
    }

    public function allKey(): string
    {
        return $this->cacheKeys->make('access', 'permission_map', 'all');
    }

    public function roleKey(string $role): string
    {
        return $this->cacheKeys->make('access', 'permission_map', 'role', [$role]);
    }

    public function userKey(Authenticatable $user): string
    {
        return $this->cacheKeys->make('access', 'permission_map', 'user', [
            $user::class,
            (string) $user->getAuthIdentifier(),
        ]);
    }
}
