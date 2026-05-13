<?php

declare(strict_types=1);

namespace Tests\Feature;

use Carbon\CarbonImmutable;
use YezzMedia\OpsSecurity\Contracts\SecurityRequestBroker;
use YezzMedia\OpsSecurity\Data\SecurityDecisionRecordData;
use YezzMedia\OpsSecurity\Data\SecurityRuntimeEvidenceData;

final class FakeSecurityRequestBroker implements SecurityRequestBroker
{
    /**
     * @var array<int, array<string, mixed>>
     */
    public array $submitted = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $runtime = [];

    public function submit(string $requestKey, array $payload = [], ?string $source = null, ?string $actor = null): SecurityDecisionRecordData
    {
        $this->submitted[] = [
            'request_key' => $requestKey,
            'payload' => $payload,
            'source' => $source,
            'actor' => $actor,
        ];

        return new SecurityDecisionRecordData(
            requestKey: $requestKey,
            package: 'yezzmedia/laravel-access',
            domain: 'identity',
            control: 'privileged_mfa',
            scope: 'super-admin',
            requestedLevel: 'required',
            requestedEnforcementMode: 'observe_only',
            effectiveLevel: 'required',
            effectiveEnforcementMode: 'observe_only',
            status: 'recorded',
            payloadPreview: [],
            hasConflict: false,
            conflictReason: null,
            source: $source,
            actor: $actor,
            recordedAt: CarbonImmutable::now(),
        );
    }

    public function recordRuntimeUsage(string $requestKey, array $payload = [], ?string $source = null, ?string $actor = null): SecurityRuntimeEvidenceData
    {
        $this->runtime[] = [
            'request_key' => $requestKey,
            'payload' => $payload,
            'source' => $source,
            'actor' => $actor,
        ];

        return new SecurityRuntimeEvidenceData(
            requestKey: $requestKey,
            package: 'yezzmedia/laravel-access',
            domain: 'identity',
            control: 'privileged_mfa',
            scope: 'super-admin',
            status: 'recorded',
            payloadPreview: [],
            source: $source,
            actor: $actor,
            recordedAt: CarbonImmutable::now(),
        );
    }

    public function requests(): array
    {
        return [];
    }

    public function decisions(): array
    {
        return [];
    }

    public function runtimeEvidence(): array
    {
        return [];
    }
}
