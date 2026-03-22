<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use Exception;
use Yii;
use yii\caching\DummyCache;
use yii\db\Connection;
use yiiunit\TestCase;

/**
 * Provides database connection management for driver-specific test classes.
 *
 * Subclasses must set {@see $driverName} to the target DBMS identifier (`mysql`, `pgsql`, `sqlite`, `oci`, `sqlsrv`).
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
abstract class BaseDatabase extends TestCase
{
    /**
     * @var array database configuration parameters loaded from test config.
     */
    protected $database;
    /**
     * @var string DBMS driver name. Must be set by a subclass.
     */
    protected $driverName;
    /**
     * @var Connection|null cached database connection instance.
     */
    private $_db;

    protected function setUp(): void
    {
        if ($this->driverName === null) {
            throw new Exception('driverName is not set for a Database.');
        }

        parent::setUp();

        $databases = self::getParam('databases');

        $this->database = $databases[$this->driverName];

        $pdoDatabase = "pdo_{$this->driverName}";

        if ($this->driverName === 'oci') {
            $pdoDatabase = 'oci8';
        }

        if (!\extension_loaded('pdo') || !\extension_loaded($pdoDatabase)) {
            $this->markTestSkipped("PDO and {$pdoDatabase} extension are required.");
        }

        $this->mockApplication();
    }

    protected function tearDown(): void
    {
        $this->_db?->close();
        $this->destroyApplication();
    }

    /**
     * Returns a database connection, creating it on the first call.
     *
     * @param bool $reset whether to recreate the connection and reload fixtures.
     * @param bool $open whether to open the connection and load fixtures.
     *
     * @return Connection database connection instance.
     */
    public function getConnection($reset = true, $open = true): Connection
    {
        if (!$reset && $this->_db !== null) {
            return $this->_db;
        }

        try {
            $this->_db = DbHelper::createConnection($this->database, $open);
        } catch (Exception $e) {
            $this->markTestSkipped('Something wrong when preparing database: ' . $e->getMessage());
        }

        return $this->_db;
    }

    /**
     * Creates a connection from a Yii-compatible config array via {@see Yii::createObject()}.
     *
     * Used by {@see getConnectionWithInvalidSlave()} for configs with extra properties (`slaves`, `serverStatusCache`)
     * that {@see DbHelper::createConnection()} does not support.
     *
     * @param array $config Yii object configuration array (`dsn`, `username`, `password`, `class`, etc.).
     * @param string|null $fixture absolute path to the SQL fixture file, or `null` to skip.
     * @param bool $open whether to open the connection and load the fixture.
     *
     * @return Connection configured connection instance.
     */
    public function prepareDatabase($config, $fixture, $open = true): Connection
    {
        if (!isset($config['class'])) {
            $config['class'] = 'yii\db\Connection';
        }

        /** @var Connection $db */
        $db = Yii::createObject($config);

        if (!$open) {
            return $db;
        }

        if ($fixture !== null) {
            DbHelper::prepareDatabase($db, $fixture);
        } else {
            $db->open();
        }

        return $db;
    }

    /**
     * Adjusts DBMS-specific identifier quoting in SQL strings.
     *
     * Delegates to {@see DbHelper::replaceQuotes()}.
     *
     * @param string $sql SQL string with `[[` / `]]` identifier markers.
     *
     * @return string SQL with driver-appropriate quoting applied.
     */
    protected function replaceQuotes(string $sql): string
    {
        return DbHelper::replaceQuotes($sql, $this->driverName);
    }

    /**
     * Returns a connection configured with an invalid slave entry for failover testing.
     *
     * @return Connection connection with an empty slave configuration.
     */
    protected function getConnectionWithInvalidSlave(): Connection
    {
        $config = [
            ...$this->database,
            'serverStatusCache' => new DummyCache(),
            'slaves' => [
                [], // invalid config
            ],
        ];

        if (isset($config['fixture'])) {
            $fixture = $config['fixture'];
            unset($config['fixture']);
        } else {
            $fixture = null;
        }

        return $this->prepareDatabase($config, $fixture, true);
    }
}
