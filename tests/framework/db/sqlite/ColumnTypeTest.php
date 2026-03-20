<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use PHPUnit\Framework\Attributes\Group;
use yiiunit\base\db\BaseColumnType;
use yiiunit\framework\db\sqlite\providers\ColumnTypeProvider;

/**
 * Unit test for column type mapping with SQLite driver.
 *
 * {@see ColumnTypeProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('sqlite')]
#[Group('column-type')]
final class ColumnTypeTest extends BaseColumnType
{
    protected $driverName = 'sqlite';

    public function columnTypes(): array
    {
        return $this->resolveColumnTypes(ColumnTypeProvider::columnTypes());
    }
}
