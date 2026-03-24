<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use PHPUnit\Framework\Attributes\Group;
use yiiunit\base\db\BaseTransaction;

/**
 * Unit tests for {@see \yii\db\Transaction} for the MySQL driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mysql')]
#[Group('transaction')]
final class TransactionTest extends BaseTransaction
{
    protected $driverName = 'mysql';
}
