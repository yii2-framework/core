<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use PHPUnit\Framework\Attributes\Group;
use yii\db\TransactionIsolationLevel;
use yiiunit\base\db\BaseTransaction;

/**
 * Unit tests for {@see \yii\db\Transaction} for the PostgreSQL driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('pgsql')]
#[Group('transaction')]
final class TransactionTest extends BaseTransaction
{
    protected $driverName = 'pgsql';

    /**
     * PostgreSQL requires setting the isolation level after the transaction has started.
     */
    public function testIsolationLevel(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(TransactionIsolationLevel::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(TransactionIsolationLevel::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(TransactionIsolationLevel::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(TransactionIsolationLevel::SERIALIZABLE);
        $transaction->commit();

        $transaction = $connection->beginTransaction();
        $transaction->setIsolationLevel(TransactionIsolationLevel::SERIALIZABLE->value . ' READ ONLY DEFERRABLE');
        $transaction->commit();

        self::expectNotToPerformAssertions();
    }
}
