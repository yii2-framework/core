<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use Exception;
use yii\base\InvalidArgumentException;
use yii\base\NotSupportedException;
use yii\db\Connection;
use yii\db\TransactionIsolationLevel;

/**
 * Base test class for transaction behavior across all database drivers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
abstract class BaseTransaction extends BaseDatabase
{
    public function testBeginCommitRollback(): void
    {
        $connection = $this->getConnection();

        self::assertNull(
            $connection->transaction,
            'No active transaction before begin.',
        );

        $transaction = $connection->beginTransaction();

        self::assertNotNull(
            $connection->transaction,
            'Active transaction after begin.',
        );
        self::assertTrue(
            $transaction->isActive,
            'Transaction should be active after begin.',
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction'])
            ->execute();
        $transaction->rollBack();

        self::assertFalse(
            $transaction->isActive,
            'Transaction should be inactive after rollback.',
        );
        self::assertNull(
            $connection->transaction,
            'No active transaction after rollback.',
        );
        self::assertEquals(
            0,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL,
            )->queryScalar(),
            'Rolled-back row should not be persisted.',
        );

        $transaction = $connection->beginTransaction();
        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction'])
            ->execute();
        $transaction->commit();

        self::assertFalse(
            $transaction->isActive,
            'Transaction should be inactive after commit.',
        );
        self::assertNull(
            $connection->transaction,
            'No active transaction after commit.',
        );
        self::assertEquals(
            1,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL,
            )->queryScalar(),
            'Committed row should be persisted.',
        );
    }

    public function testIsolationLevel(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::READ_UNCOMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::READ_COMMITTED);
        $transaction->commit();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::REPEATABLE_READ);
        $transaction->commit();

        $transaction = $connection->beginTransaction(TransactionIsolationLevel::SERIALIZABLE);
        $transaction->commit();

        self::expectNotToPerformAssertions();
    }

    public function testCallbackException(): void
    {
        $connection = $this->getConnection();

        $exceptionThrown = false;

        try {
            $connection->transaction(
                static function () use ($connection): never {
                    $connection->createCommand()
                        ->insert('profile', ['description' => 'test transaction shortcut'])
                        ->execute();

                    throw new Exception('Exception in transaction shortcut.');
                }
            );
        } catch (Exception $e) {
            $exceptionThrown = true;

            self::assertSame(
                'Exception in transaction shortcut.',
                $e->getMessage(),
            );
        }

        self::assertTrue(
            $exceptionThrown,
            'Exception should have been thrown.',
        );

        $profilesCount = $connection
            ->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction shortcut'
                SQL,
            )
            ->queryScalar();

        self::assertEquals(
            0,
            $profilesCount,
            'Row should not be persisted after callback exception.',
        );
    }

    public function testCallbackCommit(): void
    {
        $connection = $this->getConnection();

        $result = $connection->transaction(
            static function () use ($connection): bool {
                $connection->createCommand()
                    ->insert('profile', ['description' => 'test transaction shortcut'])
                    ->execute();

                return true;
            }
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
            'Row should be persisted after successful callback.',
        );
    }

    public function testCallbackWithIsolationLevel(): void
    {
        $connection = $this->getConnection();

        $result = $connection->transaction(
            static function (Connection $db): bool {
                $db->createCommand()
                    ->insert('profile', ['description' => 'test transaction shortcut'])
                    ->execute();

                return true;
            },
            TransactionIsolationLevel::READ_UNCOMMITTED,
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

    /**
     * @see https://github.com/yiisoft/yii2/issues/9851
     */
    public function testNestedTransaction(): void
    {
        $connection = $this->getConnection();

        $connection->transaction(
            static function (Connection $db): void {
                self::assertNotNull(
                    $db->transaction,
                    'Outer transaction should be active.',
                );

                $db->transaction(
                    static function (Connection $db): void {
                        self::assertNotNull(
                            $db->transaction,
                            'Inner transaction should be active.',
                        );
                        $db->transaction->rollBack();
                    }
                );

                self::assertNotNull(
                    $db->transaction,
                    'Outer transaction should remain active after inner rollback.',
                );
            }
        );
    }

    public function testNestedTransactionNotSupported(): void
    {
        $connection = $this->getConnection();

        $connection->enableSavepoint = false;

        $connection->transaction(
            function (Connection $db): void {
                self::assertNotNull(
                    $db->transaction,
                    'Transaction should be active.',
                );

                $this->expectException(NotSupportedException::class);
                $this->expectExceptionMessage('Transaction not started: nested transaction not supported.');

                $db->beginTransaction();
            }
        );
    }

    public function testThrowInvalidArgumentExceptionWhenCreateSavepointWithUnsafeName(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid savepoint name: 'DROP TABLE users'.");

        $transaction->createSavepoint('DROP TABLE users');
    }

    public function testThrowInvalidArgumentExceptionWhenReleaseSavepointWithUnsafeName(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid savepoint name: 'DROP TABLE users'.");

        $transaction->releaseSavepoint('DROP TABLE users');
    }

    public function testThrowInvalidArgumentExceptionWhenRollBackSavepointWithUnsafeName(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Invalid savepoint name: 'DROP TABLE users'.");

        $transaction->rollBackSavepoint('DROP TABLE users');
    }

    public function testRollbackTransactionsWithSavePoints(): void
    {
        $connection = $this->getConnection();

        $connection->open();
        $transaction = $connection->beginTransaction();

        self::assertEquals(
            1,
            $transaction->level,
            "Transaction level should be '1' after begin.",
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction'])
            ->execute();
        $transaction->begin();

        self::assertEquals(
            2,
            $transaction->level,
            "Transaction level should be '2' after nested begin.",
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction'])
            ->execute();
        $transaction->rollBack();

        self::assertEquals(
            1,
            $transaction->level,
            "Transaction level should be '1' after nested rollback.",
        );
        self::assertTrue(
            $transaction->isActive,
            'Outer transaction should remain active after nested rollback.',
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction'])
            ->execute();
        $transaction->rollBack();

        self::assertEquals(
            0,
            $transaction->level,
            "Transaction level should be '0' after outer rollback.",
        );
        self::assertFalse(
            $transaction->isActive,
            'Transaction should be inactive after full rollback.',
        );
        self::assertNull(
            $connection->transaction,
            'Connection should have no active transaction after full rollback.',
        );
        self::assertEquals(
            0,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction'
                SQL,
            )->queryScalar(),
            'No rows should be persisted after full rollback of both levels.',
        );
    }

    public function testPartialRollbackTransactionsWithSavePoints(): void
    {
        $connection = $this->getConnection();

        $connection->open();
        $transaction = $connection->beginTransaction();

        self::assertEquals(
            1,
            $transaction->level,
            "Transaction level should be '1' after begin.",
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction1'])
            ->execute();
        $transaction->begin();

        self::assertEquals(
            2,
            $transaction->level,
            "Transaction level should be '2' after nested begin.",
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction2'])
            ->execute();
        $transaction->rollBack();

        self::assertEquals(
            1,
            $transaction->level,
            "Transaction level should be '1' after nested rollback.",
        );
        self::assertTrue(
            $transaction->isActive,
            'Outer transaction should remain active after nested rollback.',
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction3'])
            ->execute();
        $transaction->commit();

        self::assertEquals(
            0,
            $transaction->level,
            "Transaction level should be '0' after outer commit.",
        );
        self::assertFalse(
            $transaction->isActive,
            'Transaction should be inactive after commit.',
        );
        self::assertNull(
            $connection->transaction,
            'Connection should have no active transaction after commit.',
        );
        self::assertEquals(
            1,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'
                SQL,
            )->queryScalar(),
            'Row inserted in outer transaction before savepoint should be persisted.',
        );
        self::assertEquals(
            0,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL,
            )->queryScalar(),
            'Row inserted inside rolled-back savepoint should not be persisted.',
        );
        self::assertEquals(
            1,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'
                SQL,
            )->queryScalar(),
            'Row inserted in outer transaction after savepoint rollback should be persisted.',
        );
    }

    public function testCommitTransactionsWithSavepoints(): void
    {
        $connection = $this->getConnection();

        $transaction = $connection->beginTransaction();

        self::assertEquals(
            1,
            $transaction->level,
            "Transaction level should be '1' after begin.",
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction1'])
            ->execute();
        $transaction->begin();

        self::assertEquals(
            2,
            $transaction->level,
            "Transaction level should be '2' after nested begin.",
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction2'])
            ->execute();
        $transaction->commit();

        self::assertEquals(
            1,
            $transaction->level,
            "Transaction level should be '1' after nested commit.",
        );

        $connection->createCommand()
            ->insert('profile', ['description' => 'test transaction3'])
            ->execute();
        $transaction->commit();

        self::assertEquals(
            0,
            $transaction->level,
            "Transaction level should be '0' after outer commit.",
        );
        self::assertFalse(
            $transaction->isActive,
            'Transaction should be inactive after full commit.',
        );
        self::assertNull(
            $connection->transaction,
            'Connection should have no active transaction after full commit.',
        );
        self::assertEquals(
            1,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction1'
                SQL,
            )->queryScalar(),
            'Row inserted in outer transaction should be persisted after full commit.',
        );
        self::assertEquals(
            1,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction2'
                SQL,
            )->queryScalar(),
            'Row inserted inside committed savepoint should be persisted.',
        );
        self::assertEquals(
            1,
            $connection->createCommand(
                <<<SQL
                SELECT COUNT(*) FROM {{profile}} WHERE [[description]] = 'test transaction3'
                SQL,
            )->queryScalar(),
            'Row inserted after savepoint commit should be persisted.',
        );
    }
}
