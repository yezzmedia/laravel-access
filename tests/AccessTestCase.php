<?php

declare(strict_types=1);

namespace YezzMedia\Access\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\PermissionServiceProvider;
use YezzMedia\Access\AccessServiceProvider;
use YezzMedia\Foundation\Testing\FoundationTestCase;

/**
 * Provides a realistic Testbench baseline for access package tests.
 */
abstract class AccessTestCase extends FoundationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensurePermissionsTableExists();
        $this->ensureRolesTableExists();
        $this->ensureRoleHasPermissionsTableExists();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ...parent::getPackageProviders($app),
            PermissionServiceProvider::class,
            AccessServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    private function ensurePermissionsTableExists(): void
    {
        $tableName = (string) config('permission.table_names.permissions', 'permissions');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
    }

    private function ensureRolesTableExists(): void
    {
        $tableName = (string) config('permission.table_names.roles', 'roles');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, static function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });
    }

    private function ensureRoleHasPermissionsTableExists(): void
    {
        $tableName = (string) config('permission.table_names.role_has_permissions', 'role_has_permissions');
        $permissionPivotKey = $this->permissionPivotKey();
        $rolePivotKey = $this->rolePivotKey();

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($permissionPivotKey, $rolePivotKey): void {
            $table->unsignedBigInteger($permissionPivotKey);
            $table->unsignedBigInteger($rolePivotKey);
            $table->primary([$permissionPivotKey, $rolePivotKey]);
        });
    }

    private function permissionPivotKey(): string
    {
        $column = config('permission.column_names.permission_pivot_key');

        if (! is_string($column) || $column === '') {
            return 'permission_id';
        }

        return $column;
    }

    private function rolePivotKey(): string
    {
        $column = config('permission.column_names.role_pivot_key');

        if (! is_string($column) || $column === '') {
            return 'role_id';
        }

        return $column;
    }
}
