<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

final class TestUser extends Authenticatable
{
    use HasRoles;

    protected string $guard_name = 'web';

    protected $table = 'users';

    /**
     * @var array<int, string>
     */
    protected $guarded = [];
}
