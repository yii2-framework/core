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
 * Unit test for {@see \yii\caching\DbCache} with MSSQL driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mssql')]
#[Group('caching')]
final class MssqlCacheTest extends MysqlCacheTest
{
    protected static string $driverName = 'sqlsrv';

    protected function cacheTableColumns(): array
    {
        return [
            'id' => 'VARCHAR(128) NOT NULL PRIMARY KEY',
            'expire' => 'INT',
            'data' => 'VARBINARY(MAX)',
        ];
    }
}
