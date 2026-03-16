<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\db\sqlite;

use yii\db\Expression;

use function strcasecmp;
use function strlen;
use function substr;
use function trim;

/**
 * Represents the metadata of a column in a SQLite database table.
 *
 * Extends the base {@see \yii\db\ColumnSchema} with SQLite-specific default value handling:
 * - Normalizes `null`, empty string, and `'null'` literals to `null`.
 * - Converts `CURRENT_TIMESTAMP` defaults on timestamp columns to {@see Expression} instances.
 * - Strips surrounding single/double-quote wrappers from string defaults.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * Converts a SQLite column default value to its PHP representation.
     *
     * Handles SQLite-specific default value formats:
     * - `null`, empty string, or `'null'` literal -> `null`.
     * - `CURRENT_TIMESTAMP` on timestamp columns -> `Expression('CURRENT_TIMESTAMP')`.
     * - Single/double-quote-wrapped string defaults -> unwrapped value.
     * - Everything else -> delegates to `parent::defaultPhpTypecast()`.
     *
     * @param mixed $value default value in SQLite format.
     *
     * @return mixed converted value.
     *
     * @since 2.2
     */
    public function defaultPhpTypecast($value)
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if ($value === '' || $value === 'null') {
            return null;
        }

        if ($this->type === 'timestamp' && strcasecmp($value, 'CURRENT_TIMESTAMP') === 0) {
            return new Expression('CURRENT_TIMESTAMP');
        }

        // Strip surrounding single or double quotes from string defaults.
        if (strlen($value) > 1 && ($value[0] === "'" || $value[0] === '"') && $value[-1] === $value[0]) {
            $value = substr($value, 1, -1);
        }

        return parent::defaultPhpTypecast($value);
    }
}
