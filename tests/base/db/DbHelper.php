<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use yii\db\Connection;

use function explode;
use function file_get_contents;
use function preg_replace;
use function str_replace;
use function trim;

/**
 * Provides static helpers for database test setup: fixture loading and SQL quote adjustment.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class DbHelper
{
    /**
     * Creates a database connection from config parameters.
     *
     * When `$open` is `true` (default), opens the connection and loads the fixture if present.
     * When `$open` is `false`, returns the connection without opening it.
     *
     * @param array $params database config (`dsn`, optional `username`, `password`, `fixture`).
     * @param bool $open whether to open the connection and load the fixture.
     *
     * @return Connection connection instance.
     */
    public static function createConnection(array $params, bool $open = true): Connection
    {
        $db = new Connection();

        $db->dsn = $params['dsn'];

        if (isset($params['username'])) {
            $db->username = $params['username'];
        }

        if (isset($params['password'])) {
            $db->password = $params['password'];
        }

        if (!$open) {
            return $db;
        }

        if (isset($params['fixture'])) {
            self::prepareDatabase($db, $params['fixture']);
        } else {
            $db->open();
        }

        return $db;
    }

    /**
     * Opens a database connection and loads a SQL fixture file.
     *
     * Handles Oracle-specific fixture format with `/* STATEMENTS * /` and `/* TRIGGERS * /` markers.
     *
     * @param Connection $db database connection instance (not yet opened).
     * @param string $fixture absolute path to the SQL fixture file.
     */
    public static function prepareDatabase(Connection $db, string $fixture): void
    {
        $db->open();

        $driverName = $db->getDriverName();

        if ($driverName === 'oci') {
            [$drops, $creates] = explode('/* STATEMENTS */', file_get_contents($fixture), 2);
            [$statements, $triggers, $data] = explode('/* TRIGGERS */', $creates, 3);
            $lines = [
                ...explode('--', $drops),
                ...explode(';', $statements),
                ...explode('/', $triggers),
                ...explode(';', $data),
            ];
        } else {
            $lines = explode(';', file_get_contents($fixture));
        }

        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $db->pdo->exec($line);
            }
        }
    }

    /**
     * Adjusts DBMS-specific identifier quoting in SQL strings.
     *
     * Replaces `[[` and `]]` markers with the appropriate quote characters for each driver.
     *
     * @param string $sql SQL string with `[[` / `]]` identifier markers.
     * @param string $driverName DBMS driver name (`mysql`, `sqlite`, `pgsql`, `oci`, `sqlsrv`, `dblib`).
     *
     * @return string SQL with driver-appropriate quoting applied.
     */
    public static function replaceQuotes(string $sql, string $driverName): string
    {
        return match ($driverName) {
            'mysql', 'sqlite' => str_replace(['[[', ']]'], '`', $sql),
            'oci' => str_replace(['[[', ']]'], '"', $sql),
            'pgsql' => str_replace(
                ['\\[', '\\]'],
                ['[', ']'],
                preg_replace('/(\[\[)|((?<!(\[))]])/', '"', $sql),
            ),
            'sqlsrv', 'dblib' => str_replace(['[[', ']]'], ['[', ']'], $sql),
            default => $sql,
        };
    }
}
