<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\caching;

use PHPUnit\Framework\Attributes\Group;

/**
 * Unit test for {@see \yii\caching\DbCache} with SQLite driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('sqlite')]
#[Group('caching')]
final class SqliteCacheTest extends MysqlCacheTest
{
    protected static string $driverName = 'sqlite';

    protected function cacheTableColumns(): array
    {
        return [
            'id' => 'VARCHAR(128) NOT NULL PRIMARY KEY',
            'expire' => 'INTEGER',
            'data' => 'BLOB',
        ];
    }
}
