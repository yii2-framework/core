<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * Represents the query execution modes used by {@see Command::queryInternal()} to determine how results are fetched
 * from a prepared statement, replacing the previous dynamic method dispatch via `call_user_func_array()`.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
enum QueryMode: int
{
    /**
     * Returns a {@see DataReader} for forward-only row traversal.
     */
    case CURSOR = 0;

    /**
     * Fetches all rows at once as an array of associative arrays.
     */
    case ALL = 1;

    /**
     * Fetches a single row as an associative array, or `false` if no results.
     */
    case ONE = 2;

    /**
     * Fetches the first column of all rows as a flat array.
     */
    case COLUMN = 3;

    /**
     * Fetches a single scalar value from the first column of the first row.
     */
    case SCALAR = 4;
}
