<?php

use Stancl\Tenancy\Database\Models\Domain;

return [

    'tenant_model' => \App\Models\Tenant::class,

    // ❌ REMOVE UUID (optional but recommended for simplicity)
    // 'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'domain_model' => Domain::class,
    'asset_helper_tenancy' => false,

    'central_domains' => [
        'localhost',
        '127.0.0.1',
        'square.in',
    ],

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        // Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class, // Disabled: File & Database cache stores do not support tagging
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
        // Isolates each tenant's session cookie so that refreshing one
        // tab (e.g. store admin) never picks up another tab's session
        // (e.g. hema).  Fixes the cross-tab redirect bug.
        App\Tenancy\SessionTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => env('DB_CONNECTION', 'pgsql'),

        'template_tenant_connection' => null,

        // ✅ FIX: Correct generator
        'database_name_generator' => Stancl\Tenancy\Database\DatabaseNameGenerator::class,

        'prefix' => 'tenant_',
        'suffix' => '',

        'managers' => [
            'pgsql' => Stancl\Tenancy\TenantDatabaseManagers\PostgreSQLDatabaseManager::class,
        ],
    ],

    'cache' => [
        'tag_base' => 'tenant',
    ],

    'filesystem' => [
        'suffix_base' => 'tenant',
        'disks' => [
            'local',
            'public',
        ],
        'root_override' => [
            'local'  => '%storage_path%/app/',
            'public' => '%storage_path%/app/public/',
        ],
    ],

    'redis' => [
        'prefix_base'  => 'tenant',
        'prefixed_connections' => [],
    ],

    'features' => [
        Stancl\Tenancy\Features\UniversalRoutes::class,
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path'  => 'database/migrations/tenant',
        '--realpath' => false,
    ],

    'seeder_parameters' => [
        '--force' => true,
    ],
];