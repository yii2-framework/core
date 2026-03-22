<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\caching;

use PHPUnit\Framework\Attributes\Group;
use yii\caching\DbCache;
use yii\db\Connection;
use yiiunit\base\caching\BaseCache;
use yiiunit\base\db\DbHelper;
use function time;

/**
 * Unit test for  {@see \yii\caching\DbCache} with MySQL driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mysql')]
#[Group('caching')]
class MysqlCacheTest extends BaseCache
{
    protected static string $driverName = 'mysql';
    private $_cacheInstance;
    private $_connection;

    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('cache') !== null) {
            $db->createCommand()->dropTable('cache')->execute();
        }

        $db->createCommand()
            ->createTable('cache', $this->cacheTableColumns())
            ->execute();
    }

    /**
     * Returns column definitions for the cache table, specific to each driver.
     */
    protected function cacheTableColumns(): array
    {
        return [
            'id' => 'CHAR(128) NOT NULL PRIMARY KEY',
            'expire' => 'INT DEFAULT NULL',
            'data' => 'LONGBLOB',
        ];
    }

    public function getConnection(): Connection
    {
        if ($this->_connection === null) {
            $databases = self::getParam('databases');
            $this->_connection = DbHelper::createConnection($databases[static::$driverName]);
        }

        return $this->_connection;
    }

    protected function getCacheInstance(): DbCache
    {
        if ($this->_cacheInstance === null) {
            $this->_cacheInstance = new DbCache(['db' => $this->getConnection()]);
        }

        return $this->_cacheInstance;
    }

    public function testExpire(): void
    {
        $cache = $this->getCacheInstance();

        static::$time = time();

        self::assertTrue(
            $cache->set('expire_test', 'expire_test', 2),
            "Cache set should return 'true'.",
        );

        static::$time++;

        self::assertSame(
            'expire_test',
            $cache->get('expire_test'),
            'Cache value should be available before expiry.',
        );

        static::$time++;

        self::assertFalse(
            $cache->get('expire_test'),
            "Cache value should return 'false' after expiry.",
        );
    }

    public function testExpireAdd(): void
    {
        $cache = $this->getCacheInstance();

        static::$time = time();

        self::assertTrue(
            $cache->add('expire_testa', 'expire_testa', 2),
            "Cache add should return 'true'.",
        );

        static::$time++;

        self::assertSame(
            'expire_testa',
            $cache->get('expire_testa'),
            'Cache value should be available before expiry.',
        );

        static::$time++;

        self::assertFalse(
            $cache->get('expire_testa'),
            "Cache value should return 'false' after expiry.",
        );
    }

    public function testSynchronousSetWithTheSameKey(): void
    {
        $key = 'sync-test-key';
        $value = 'sync-test-value';

        $cache = $this->getCacheInstance();

        static::$time = time();

        self::assertTrue(
            $cache->set($key, $value, 60),
            "First cache set should return 'true'.",
        );
        self::assertTrue(
            $cache->set($key, $value, 60),
            "Second cache set with the same key should return 'true'.",
        );
        self::assertSame(
            $value,
            $cache->get($key),
            'Cache value should match after synchronous set with the same key.',
        );
    }
}
