<?php

declare(strict_types=1);

namespace Tests;

use YezzMedia\Access\Tests\AccessTestCase;
use YezzMedia\Access\Tests\Concerns\InteractsWithPermissionSync;
use YezzMedia\Access\Tests\Concerns\InteractsWithRoles;

abstract class TestCase extends AccessTestCase
{
    use InteractsWithPermissionSync;
    use InteractsWithRoles;
}
