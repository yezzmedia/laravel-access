<?php

declare(strict_types=1);

use Tests\Feature\FakeDoctorSecurityRequestBroker;
use YezzMedia\Access\Doctor\SuperAdminConfiguredCheck;

if (interface_exists('YezzMedia\\OpsSecurity\\Contracts\\SecurityRequestBroker')) {
    eval(<<<'PHP'
        namespace Tests\Feature;

        use Carbon\CarbonImmutable;
        use YezzMedia\OpsSecurity\Contracts\SecurityRequestBroker;
        use YezzMedia\OpsSecurity\Data\SecurityDecisionRecordData;
        use YezzMedia\OpsSecurity\Data\SecurityRuntimeEvidenceData;

        final class FakeDoctorSecurityRequestBroker implements SecurityRequestBroker
        {
            /**
             * @var array<int, array<string, mixed>>
             */
            public array $submitted = [];

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
        PHP);
}

it('skips when super-admin bootstrap is disabled', function (): void {
    config()->set('access.super_admin.enabled', false);
    config()->set('access.super_admin.role_name', null);

    $result = app(SuperAdminConfiguredCheck::class)->run();

    expect($result->key)->toBe('super_admin_configured')
        ->and($result->package)->toBe('yezzmedia/laravel-access')
        ->and($result->status)->toBe('skipped')
        ->and($result->isBlocking)->toBeFalse();
});

it('fails when super-admin bootstrap is enabled without a valid role name', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', '   ');

    $result = app(SuperAdminConfiguredCheck::class)->run();

    expect($result->status)->toBe('failed')
        ->and($result->isBlocking)->toBeTrue()
        ->and($result->context)->toMatchArray([
            'exception' => InvalidArgumentException::class,
        ]);
});

it('passes when super-admin bootstrap is enabled with a valid role name', function (): void {
    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', ' super_admin ');

    $result = app(SuperAdminConfiguredCheck::class)->run();

    expect($result->status)->toBe('passed')
        ->and($result->isBlocking)->toBeFalse()
        ->and($result->context)->toBe([
            'role_name' => 'super_admin',
        ]);
});

it('reports super-admin visibility when the doctor check passes and a broker is available', function (): void {
    if (! interface_exists('YezzMedia\\OpsSecurity\\Contracts\\SecurityRequestBroker') || ! class_exists('Tests\\Feature\\FakeDoctorSecurityRequestBroker')) {
        $this->markTestSkipped('Ops security broker contract is not available in this test environment.');
    }

    config()->set('access.super_admin.enabled', true);
    config()->set('access.super_admin.role_name', 'super_admin');

    $broker = new FakeDoctorSecurityRequestBroker;

    app()->instance('YezzMedia\\OpsSecurity\\Contracts\\SecurityRequestBroker', $broker);

    $result = app(SuperAdminConfiguredCheck::class)->run();

    expect($result->status)->toBe('passed')
        ->and($broker->submitted)->toHaveCount(1)
        ->and($broker->submitted[0]['request_key'])->toBe('access.request.identity.privileged-mfa')
        ->and($broker->submitted[0]['source'])->toBe(SuperAdminConfiguredCheck::class)
        ->and($broker->submitted[0]['actor'])->toBe('access')
        ->and($broker->submitted[0]['payload'])->toMatchArray([
            'role' => 'super_admin',
            'channel' => 'doctor_check',
            'ability' => null,
            'actor_reference' => 'system',
        ]);
});
