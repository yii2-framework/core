<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\rbac;

use PHPUnit\Framework\Attributes\Group;

/**
 * Unit test for {@see \yii\rbac\DbManager} with MSSQL driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('rbac')]
#[Group('mssql')]
class MsSQLManagerTest extends DbManagerTestCase
{
    protected static $driverName = 'sqlsrv';
}
