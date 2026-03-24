<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * Represents the standard SQL transaction isolation levels used by {@see Transaction::begin()} and
 * {@see Transaction::setIsolationLevel()} to control concurrent access behavior.
 *
 * @link https://en.wikipedia.org/wiki/Isolation_%28database_systems%29#Isolation_levels
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
enum TransactionIsolationLevel: string
{
    /**
     * Prevents dirty reads: transactions only see committed changes from other transactions.
     */
    case READ_COMMITTED = 'READ COMMITTED';

    /**
     * Allows dirty reads: transactions can see uncommitted changes from other transactions.
     */
    case READ_UNCOMMITTED = 'READ UNCOMMITTED';

    /**
     * Prevents non-repeatable reads: repeated reads within a transaction return the same result.
     */
    case REPEATABLE_READ = 'REPEATABLE READ';

    /**
     * Full isolation: transactions execute as if they were the only transaction running.
     */
    case SERIALIZABLE = 'SERIALIZABLE';
}
