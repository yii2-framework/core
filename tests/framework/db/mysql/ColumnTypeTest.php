<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use PHPUnit\Framework\Attributes\Group;
use yiiunit\base\db\BaseColumnType;
use yiiunit\framework\db\mysql\providers\ColumnTypeProvider;

/**
 * Unit test for column type mapping with MySQL driver.
 *
 * {@see ColumnTypeProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mysql')]
#[Group('column-type')]
final class ColumnTypeTest extends BaseColumnType
{
    protected $driverName = 'mysql';

    public function columnTypes(): array
    {
        return $this->resolveColumnTypes(ColumnTypeProvider::columnTypes());
    }
}
