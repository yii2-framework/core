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
 * Unit test for {@see \yii\caching\DbCache} with Oracle driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('oci')]
#[Group('caching')]
final class OciCacheTest extends MysqlCacheTest
{
    protected static string $driverName = 'oci';

    protected function cacheTableColumns(): array
    {
        return [
            'id' => 'VARCHAR2(128) NOT NULL PRIMARY KEY',
            'expire' => 'INTEGER',
            'data' => 'BLOB',
        ];
    }
}
