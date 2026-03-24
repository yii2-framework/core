<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db;

use Yii;
use yii\base\InvalidArgumentException;
use yii\base\InvalidConfigException;
use yii\base\NotSupportedException;

/**
 * Transaction represents a DB transaction.
 *
 * It is usually created by calling [[Connection::beginTransaction()]].
 *
 * The following code is a typical example of using transactions (note that some
 * DBMS may not support transactions):
 *
 * ```
 * $transaction = $connection->beginTransaction();
 * try {
 *     $connection->createCommand($sql1)->execute();
 *     $connection->createCommand($sql2)->execute();
 *     //.... other SQL executions
 *     $transaction->commit();
 * } catch (\Exception $e) {
 *     $transaction->rollBack();
 *     throw $e;
 * }
 * ```
 *
 * @property-read bool $isActive Whether this transaction is active. Only an active transaction can
 * [[commit()]] or [[rollBack()]].
 * @property-write string|TransactionIsolationLevel $isolationLevel The transaction isolation level to use for this
 * transaction. This can be a [[TransactionIsolationLevel]] enum case or a string containing DBMS specific syntax to be
 * used after `SET TRANSACTION ISOLATION LEVEL`.
 * @property-read int $level The current nesting level of the transaction.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Transaction extends \yii\base\BaseObject
{
    /**
     * @var Connection the database connection that this transaction is associated with.
     */
    public $db;

    /**
     * @var int the nesting level of the transaction. 0 means the outermost level.
     */
    private int $_level = 0;

    /**
     * Returns a value indicating whether this transaction is active.
     *
     * @return bool whether this transaction is active. Only an active transaction can [[commit()]] or [[rollBack()]].
     */
    public function getIsActive(): bool
    {
        return $this->_level > 0 && $this->db && $this->db->isActive;
    }

    /**
     * Begins a transaction.
     *
     * @param TransactionIsolationLevel|string|null $isolationLevel The [isolation level][] to use for this transaction.
     * This can be a [[TransactionIsolationLevel]] enum case or a string containing DBMS specific syntax to be used
     * after `SET TRANSACTION ISOLATION LEVEL`.
     * If not specified (`null`) the isolation level will not be set explicitly and the DBMS default will be used.
     *
     * > Note: This setting does not work for PostgreSQL, where setting the isolation level before the transaction has
     * no effect. You have to call [[setIsolationLevel()]] in this case after the transaction has started.
     *
     * > Note: Some DBMS allow setting of the isolation level only for the whole connection so subsequent transactions
     * may get the same isolation level even if you did not specify any. When using this feature you may need to set the
     * isolation level for all transactions explicitly to avoid conflicting settings.
     * At the time of this writing affected DBMS are MSSQL and SQLite.
     *
     * [isolation level]: https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     *
     * @throws InvalidConfigException if [[db]] is `null`
     * @throws NotSupportedException if the DBMS does not support nested transactions
     * @throws Exception if DB connection fails
     */
    public function begin(TransactionIsolationLevel|string|null $isolationLevel = null): void
    {
        if ($this->db === null) {
            throw new InvalidConfigException('Transaction::db must be set.');
        }

        $this->db->open();

        if ($this->_level === 0) {
            $resolvedLevel = $isolationLevel instanceof TransactionIsolationLevel
                ? $isolationLevel->value
                : $isolationLevel;

            if ($resolvedLevel !== null) {
                $this->setTransactionIsolationLevel($resolvedLevel);
            }

            Yii::debug(
                'Begin transaction' . ($resolvedLevel ? " with isolation level {$resolvedLevel}" : ''),
                __METHOD__,
            );

            $this->db->trigger(Connection::EVENT_BEGIN_TRANSACTION);
            $this->db->pdo->beginTransaction();

            $this->_level = 1;

            return;
        }

        $schema = $this->db->getSchema();

        if ($schema->supportsSavepoint()) {
            Yii::debug(
                "Set savepoint {$this->_level}",
                __METHOD__,
            );

            // make sure the transaction wasn't autocommitted
            if ($this->db->pdo->inTransaction()) {
                $this->createSavepoint('LEVEL' . $this->_level);
            }
        } else {
            Yii::info(
                'Transaction not started: nested transaction not supported',
                __METHOD__,
            );

            throw new NotSupportedException('Transaction not started: nested transaction not supported.');
        }

        $this->_level++;
    }

    /**
     * Commits a transaction.
     *
     * @throws Exception if the transaction is not active
     */
    public function commit(): void
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to commit transaction: transaction was inactive.');
        }

        $this->_level--;
        if ($this->_level === 0) {
            Yii::debug('Commit transaction', __METHOD__);
            // make sure the transaction wasn't autocommitted
            if ($this->db->pdo->inTransaction()) {
                $this->db->pdo->commit();
            }
            $this->db->trigger(Connection::EVENT_COMMIT_TRANSACTION);
            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::debug('Release savepoint ' . $this->_level, __METHOD__);
            // make sure the transaction wasn't autocommitted
            if ($this->db->pdo->inTransaction()) {
                $this->releaseSavepoint('LEVEL' . $this->_level);
            }
        } else {
            Yii::info('Transaction not committed: nested transaction not supported', __METHOD__);
        }
    }

    /**
     * Rolls back a transaction.
     */
    public function rollBack(): void
    {
        if (!$this->getIsActive()) {
            // do nothing if transaction is not active: this could be the transaction is committed
            // but the event handler to "commitTransaction" throw an exception
            return;
        }

        $this->_level--;
        if ($this->_level === 0) {
            Yii::debug('Roll back transaction', __METHOD__);
            // make sure the transaction wasn't autocommitted
            if ($this->db->pdo->inTransaction()) {
                $this->db->pdo->rollBack();
            }
            $this->db->trigger(Connection::EVENT_ROLLBACK_TRANSACTION);
            return;
        }

        $schema = $this->db->getSchema();
        if ($schema->supportsSavepoint()) {
            Yii::debug('Roll back to savepoint ' . $this->_level, __METHOD__);
            // make sure the transaction wasn't autocommitted
            if ($this->db->pdo->inTransaction()) {
                $this->rollBackSavepoint('LEVEL' . $this->_level);
            }
        } else {
            Yii::info('Transaction not rolled back: nested transaction not supported', __METHOD__);
        }
    }

    /**
     * Sets the transaction isolation level for this transaction.
     *
     * This method can be used to set the isolation level while the transaction is already active.
     * However this is not supported by all DBMS so you might rather specify the isolation level directly when calling
     * [[begin()]].
     * @param TransactionIsolationLevel|string $level The transaction isolation level to use for this transaction.
     * This can be a [[TransactionIsolationLevel]] enum case or a string containing DBMS specific syntax to be used
     * after `SET TRANSACTION ISOLATION LEVEL`.
     *
     * @throws Exception if the transaction is not active
     * @see https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
     */
    public function setIsolationLevel(TransactionIsolationLevel|string $level): void
    {
        if (!$this->getIsActive()) {
            throw new Exception('Failed to set isolation level: transaction was inactive.');
        }

        $resolvedLevel = $level instanceof TransactionIsolationLevel ? $level->value : $level;

        Yii::debug('Setting transaction isolation level to ' . $resolvedLevel, __METHOD__);
        $this->setTransactionIsolationLevel($resolvedLevel);
    }

    /**
     * @return int The current nesting level of the transaction.
     *
     * @since 2.0.8
     */
    public function getLevel(): int
    {
        return $this->_level;
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $name The savepoint name (alphanumeric and underscores only).
     *
     * @throws InvalidArgumentException if the name contains unsafe characters.
     *
     * @since 2.2
     */
    public function createSavepoint(string $name): void
    {
        $this->validateSavepointName($name);

        $this->db->createCommand(
            <<<SQL
            SAVEPOINT $name
            SQL
        )->execute();
    }

    /**
     * Releases an existing savepoint.
     *
     * @param string $name The savepoint name (alphanumeric and underscores only).
     *
     * @throws InvalidArgumentException if the name contains unsafe characters.
     *
     * @since 2.2
     */
    public function releaseSavepoint(string $name): void
    {
        $this->validateSavepointName($name);

        $this->db->createCommand(
            <<<SQL
            RELEASE SAVEPOINT $name
            SQL
        )->execute();
    }

    /**
     * Rolls back to a previously created savepoint.
     *
     * @param string $name The savepoint name (alphanumeric and underscores only).
     *
     * @throws InvalidArgumentException if the name contains unsafe characters.
     *
     * @since 2.2
     */
    public function rollBackSavepoint(string $name): void
    {
        $this->validateSavepointName($name);

        $this->db->createCommand(
            <<<SQL
            ROLLBACK TO SAVEPOINT $name
            SQL
        )->execute();
    }

    /**
     * Executes the DBMS-specific SQL to set the transaction isolation level.
     *
     * @param string $level The resolved isolation level string.
     *
     * @since 2.2
     */
    protected function setTransactionIsolationLevel(string $level): void
    {
        $this->db->createCommand(
            <<<SQL
            SET TRANSACTION ISOLATION LEVEL $level
            SQL
        )->execute();
    }

    /**
     * Validates that a savepoint name contains only safe characters (alphanumeric and underscores).
     *
     * @throws InvalidArgumentException if the name contains unsafe characters.
     */
    protected function validateSavepointName(string $name): void
    {
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $name)) {
            throw new InvalidArgumentException("Invalid savepoint name: '{$name}'.");
        }
    }
}
