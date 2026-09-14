<?php

use App\Services\ConfigCacheService;
use App\Util\Database\DatabaseDriver;

if (! function_exists('config_cache')) {
    function config_cache($key)
    {
        return ConfigCacheService::get($key);
    }
}

if (! function_exists('db_is_mysql_maria')) {
    /**
     * True when the active (or given) connection uses MySQL or MariaDB.
     *
     * Use instead of config('database.default') === 'mysql', which misses
     * MariaDB (Laravel exposes it as a distinct 'mariadb' driver).
     */
    function db_is_mysql_maria(?string $connection = null): bool
    {
        return DatabaseDriver::isMysqlMaria($connection);
    }
}

if (! function_exists('db_is_mysql')) {
    /**
     * True when the connection uses MySQL specifically (not MariaDB).
     *
     * Only use when behaviour must differ between MySQL and MariaDB;
     * otherwise prefer db_is_mysql_maria().
     */
    function db_is_mysql(?string $connection = null): bool
    {
        return DatabaseDriver::isMysql($connection);
    }
}

if (! function_exists('db_is_mariadb')) {
    /**
     * True when the connection uses MariaDB specifically (not MySQL).
     *
     * Only use when behaviour must differ between MySQL and MariaDB;
     * otherwise prefer db_is_mysql_maria().
     */
    function db_is_mariadb(?string $connection = null): bool
    {
        return DatabaseDriver::isMariadb($connection);
    }
}

if (! function_exists('db_is_pgsql')) {
    /**
     * True when the active (or given) connection uses PostgreSQL.
     */
    function db_is_pgsql(?string $connection = null): bool
    {
        return DatabaseDriver::isPgsql($connection);
    }
}

if (! function_exists('db_is_sqlite')) {
    /**
     * True when the active (or given) connection uses SQLite (usually tests).
     */
    function db_is_sqlite(?string $connection = null): bool
    {
        return DatabaseDriver::isSqlite($connection);
    }
}
