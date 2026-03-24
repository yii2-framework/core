<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci;

use PHPUnit\Framework\Attributes\Group;
use yii\db\Connection;
use yii\db\TransactionIsolationLevel;
use yiiunit\base\db\BaseTransaction;

/**
 * Unit tests for {@see \yii\db\oci\Transaction} for the Oracle driver.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('oci')]
#[Group('transaction')]
final class TransactionTest extends BaseTransaction
{
    protected $driverName = 'oci';

    /**
     * Oracle does not support READ UNCOMMITTED or REPEATABLE READ isolation levels.
     */
    public function testIsolationLevel(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::SERIALIZABLE);
        $transaction->commit();

        self::expectNotToPerformAssertions();
    }

    /**
     * Oracle does not support READ UNCOMMITTED; use READ COMMITTED instead.
     */
    public function testCallbackWithIsolationLevel(): void
    {
        $connection = $this->getConnection();

        $result = $connection->transaction(
            static function (Connection $db) {
                $db->createCommand()
                    ->insert('profile', ['description' => 'test transaction shortcut'])
                    ->execute();

                return true;
            },
            TransactionIsolationLevel::READ_COMMITTED,
        );

        self::assertTrue(
            $result,
            'Callback return value should be propagated.',
        );

        $profilesCount = $connection->createCommand(
            <<<SQL
            SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'
            SQL,
        )->queryScalar();

        self::assertEquals(
            1,
            $profilesCount,
            'Row should be persisted with custom isolation level.',
        );
    }
}
