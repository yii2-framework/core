<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\sqlite;

use yii\base\NotSupportedException;
use yii\db\TransactionIsolationLevel;

/**
 * Transaction represents a DB transaction for SQLite.
 *
 * Overrides `setTransactionIsolationLevel()` to use SQLite-specific `PRAGMA read_uncommitted` syntax.
 * Only `SERIALIZABLE` and `READ UNCOMMITTED` isolation levels are supported.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class Transaction extends \yii\db\Transaction
{
    /**
     * Sets the isolation level using SQLite `PRAGMA read_uncommitted`.
     *
     * @param string $level The transaction isolation level. Only `READ UNCOMMITTED` and `SERIALIZABLE` are supported.
     *
     * @throws NotSupportedException when an unsupported isolation level is used.
     *
     * @see https://www.sqlite.org/pragma.html#pragma_read_uncommitted
     */
    protected function setTransactionIsolationLevel(string $level): void
    {
        match ($level) {
            TransactionIsolationLevel::SERIALIZABLE->value => $this->db->createCommand(
                <<<SQL
                PRAGMA read_uncommitted = False;
                SQL,
            )->execute(),
            TransactionIsolationLevel::READ_UNCOMMITTED->value => $this->db->createCommand(
                <<<SQL
                PRAGMA read_uncommitted = True;
                SQL,
            )->execute(),
            default => throw new NotSupportedException(
                static::class . ' only supports transaction isolation levels READ UNCOMMITTED and SERIALIZABLE.',
            ),
        };
    }
}
