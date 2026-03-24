<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use PHPUnit\Framework\Attributes\Group;
use yii\db\TransactionIsolationLevel;
use yiiunit\base\db\BaseTransaction;

/**
 * Unit tests for {@see \yii\db\sqlite\Transaction} for the SQLite driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('sqlite')]
#[Group('transaction')]
final class TransactionTest extends BaseTransaction
{
    protected $driverName = 'sqlite';

    /**
     * SQLite only supports READ UNCOMMITTED and SERIALIZABLE isolation levels.
     */
    public function testIsolationLevel(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::READ_UNCOMMITTED);
        $transaction->rollBack();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::SERIALIZABLE);
        $transaction->rollBack();

        self::expectNotToPerformAssertions();
    }
}
