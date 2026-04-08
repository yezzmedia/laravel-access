<?php

declare(strict_types=1);

namespace YezzMedia\Access\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Foundation\Application;
use Throwable;

final class AccessSecurityVisibilityReporter
{
    private const REQUEST_KEY = 'access.request.identity.privileged-mfa';

    public function __construct(
        private readonly Application $app,
    ) {}

    public function submitPrivilegedMfaRequest(string $roleName, string $channel, ?string $source = null, ?string $actorReference = null): void
    {
        $this->submit([
            'role' => $roleName,
            'channel' => $channel,
            'ability' => null,
            'actor_reference' => $actorReference,
        ], $source, 'access');
    }

    public function recordPrivilegedMfaRuntimeUsage(
        string $roleName,
        string $ability,
        Authenticatable $user,
        ?string $source = null,
        string $channel = 'runtime',
    ): void {
        $this->recordRuntimeUsage([
            'role' => $roleName,
            'channel' => $channel,
            'ability' => $ability,
            'actor_reference' => $this->actorReference($user),
        ], $source, class_basename($user));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function submit(array $payload, ?string $source, ?string $actor): void
    {
        $broker = $this->resolveBroker();

        if ($broker === null || ! method_exists($broker, 'submit')) {
            return;
        }

        try {
            $broker->submit(self::REQUEST_KEY, $payload, $source, $actor);
        } catch (Throwable) {
            // Visibility is optional; access runtime must not fail when the observer surface is unavailable.
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function recordRuntimeUsage(array $payload, ?string $source, ?string $actor): void
    {
        $broker = $this->resolveBroker();

        if ($broker === null || ! method_exists($broker, 'recordRuntimeUsage')) {
            return;
        }

        try {
            $broker->recordRuntimeUsage(self::REQUEST_KEY, $payload, $source, $actor);
        } catch (Throwable) {
            // Visibility is optional; access runtime must not fail when the observer surface is unavailable.
        }
    }

    private function resolveBroker(): ?object
    {
        $brokerClass = 'YezzMedia\\OpsSecurity\\Contracts\\SecurityRequestBroker';

        if (! interface_exists($brokerClass) || ! $this->app->bound($brokerClass)) {
            return null;
        }

        $broker = $this->app->make($brokerClass);

        return is_object($broker) ? $broker : null;
    }

    private function actorReference(Authenticatable $user): string
    {
        $identifier = method_exists($user, 'getAuthIdentifier')
            ? $user->getAuthIdentifier()
            : spl_object_id($user);

        return sprintf('%s:%s', $user::class, (string) $identifier);
    }
}
