<?php

declare(strict_types=1);

namespace YezzMedia\Access\Tests;

use YezzMedia\Access\AccessServiceProvider;
use YezzMedia\Foundation\Testing\FoundationTestCase;

/**
 * Provides a realistic Testbench baseline for access package tests.
 */
abstract class AccessTestCase extends FoundationTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            AccessServiceProvider::class,
        ];
    }
}
