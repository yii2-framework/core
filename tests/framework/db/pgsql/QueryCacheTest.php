<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use PHPUnit\Framework\Attributes\Group;
use yii\caching\FileCache;
use yii\db\Connection;
use yii\db\Query;
use yiiunit\base\db\BaseDatabase;

/**
 * Unit test for {@see \yii\db\Connection::cache()} with PostgreSQL driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('pgsql')]
#[Group('query-cache')]
final class QueryCacheTest extends BaseDatabase
{
    protected $driverName = 'pgsql';

    public function testQueryCacheFileCache(): void
    {
        $db = $this->getConnection();

        $db->enableQueryCache = true;

        $db->queryCache = new FileCache(['cachePath' => '@yiiunit/runtime/cache']);

        $db->createCommand()
            ->delete('type')
            ->execute();
        $db->createCommand()
            ->insert(
                'type',
                [
                    'int_col' => $key = 1,
                    'char_col' => '',
                    'char_col2' => '6a3ce1a0bffe8eeb6fa986caf443e24c',
                    'float_col' => 0.0,
                    'blob_col' => 'a:1:{s:13:"template";s:1:"1";}',
                    'bool_col' => true,
                ],
            )
            ->execute();

        $value = static fn(Connection $db): bool|int|string|null => (new Query())
            ->select(['char_col2'])
            ->from('type')
            ->where(['int_col' => $key])
            ->createCommand($db)
            ->queryScalar();

        // first run return
        $result = $db->cache($value);

        self::assertSame(
            '6a3ce1a0bffe8eeb6fa986caf443e24c',
            $result,
            'First query should return the correct scalar value.',
        );

        // after the request has been cached return
        $result = $db->cache($value);

        self::assertSame(
            '6a3ce1a0bffe8eeb6fa986caf443e24c',
            $result,
            'Cached query should return the same scalar value.',
        );
    }
}
