<?php

namespace App\Util\Database;

use Illuminate\Support\Facades\DB;

/**
 * Helpers for branching on the active database driver.
 *
 * Laravel 11 ships a dedicated `mariadb` driver, so `config('database.default')`
 * returns `mariadb` (not `mysql`) when a MariaDB connection is active. MySQL and
 * MariaDB share the same SQL dialect for the branches used in this codebase, so
 * they must be treated as one group. Comparing directly against the string
 * `'mysql'` silently misclassifies MariaDB as "other" (i.e. the Postgres path).
 *
 * Use these helpers instead of comparing driver strings by hand.
 */
class DatabaseDriver
{
    /**
     * Drivers that share MySQL's SQL dialect.
     *
     * @var array<int, string>
     */
    public const MYSQL_LIKE = ['mysql', 'mariadb'];

    /**
     * The driver name for the given connection (defaults to the active one).
     *
     * Resolves the real driver rather than the connection name, so a connection
     * named `mysql` that is actually configured with the `mariadb` driver is
     * reported correctly.
     */
    public static function name(?string $connection = null): ?string
    {
        return DB::connection($connection)->getDriverName();
    }

    /**
     * True when the driver is MySQL or MariaDB.
     *
     * Prefer this over `config('database.default') === 'mysql'`, which excludes
     * MariaDB.
     */
    public static function isMysqlMaria(?string $connection = null): bool
    {
        return in_array(self::name($connection), self::MYSQL_LIKE, true);
    }

    /**
     * True when the driver is MySQL specifically (not MariaDB).
     *
     * Only use when the behaviour must differ between MySQL and MariaDB;
     * otherwise prefer isMysqlMaria().
     */
    public static function isMysql(?string $connection = null): bool
    {
        return self::name($connection) === 'mysql';
    }

    /**
     * True when the driver is MariaDB specifically (not MySQL).
     *
     * Only use when the behaviour must differ between MySQL and MariaDB;
     * otherwise prefer isMysqlMaria().
     */
    public static function isMariadb(?string $connection = null): bool
    {
        return self::name($connection) === 'mariadb';
    }

    /**
     * True when the driver is PostgreSQL.
     */
    public static function isPgsql(?string $connection = null): bool
    {
        return self::name($connection) === 'pgsql';
    }

    /**
     * True when the driver is SQLite (typically the test connection).
     */
    public static function isSqlite(?string $connection = null): bool
    {
        return self::name($connection) === 'sqlite';
    }
}
