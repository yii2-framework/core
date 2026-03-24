<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use PHPUnit\Framework\Attributes\Group;
use yiiunit\base\db\BaseTransaction;

/**
 * Unit tests for {@see \yii\db\mssql\Transaction} for the MSSQL driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mssql')]
#[Group('transaction')]
final class TransactionTest extends BaseTransaction
{
    protected $driverName = 'sqlsrv';
}
