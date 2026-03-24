<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

declare(strict_types=1);

namespace yii\db\oci;

use yii\db\Expression;
use yii\db\PdoValue;

use function is_string;
use function str_contains;
use function str_replace;
use function strcasecmp;
use function stripos;
use function strlen;
use function substr;
use function trim;
use function uniqid;

/**
 * Represents the metadata of a column in an Oracle database table.
 *
 * Extends the base {@see \yii\db\ColumnSchema} with Oracle-specific type handling:
 * - Converts string values for BLOB columns to `TO_BLOB(UTL_RAW.CAST_TO_RAW(:placeholder))` expressions.
 * - Normalizes `CURRENT_TIMESTAMP` defaults on timestamp columns to {@see Expression} instances.
 * - Strips single-quote wrappers from string defaults and nullifies server-managed timestamp literals.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * Resolves the abstract column type from the Oracle DB type.
     *
     * Maps Oracle-specific type names (`FLOAT`, `DOUBLE`, `NUMBER`, `INTEGER`, `BLOB`, `CLOB`, `TIMESTAMP`) to
     * abstract types. For `NUMBER` columns, uses {@see $scale} to distinguish `decimal` (fractional) from `integer`
     * (whole-number).
     *
     * @param string $type the Oracle DB type name (for example, `NUMBER`, `VARCHAR2`, `TIMESTAMP(6)`).
     *
     * @since 2.2
     */
    public function resolveType(string $type): void
    {
        if (str_contains($type, 'FLOAT') || str_contains($type, 'DOUBLE')) {
            $this->type = Schema::TYPE_DOUBLE;
        } elseif (str_contains($type, 'NUMBER')) {
            $this->type = $this->scale === null || $this->scale > 0
                ? Schema::TYPE_DECIMAL
                : Schema::TYPE_INTEGER;
        } elseif (str_contains($type, 'INTEGER')) {
            $this->type = Schema::TYPE_INTEGER;
        } elseif (str_contains($type, 'BLOB')) {
            $this->type = Schema::TYPE_BINARY;
        } elseif (str_contains($type, 'CLOB')) {
            $this->type = Schema::TYPE_TEXT;
        } elseif (str_contains($type, 'TIMESTAMP')) {
            $this->type = Schema::TYPE_TIMESTAMP;
        } else {
            $this->type = Schema::TYPE_STRING;
        }
    }

    /**
     * {@inheritdoc}
     *
     * Converts string values for BLOB columns to `TO_BLOB(UTL_RAW.CAST_TO_RAW(:placeholder))` expressions to avoid
     * direct string binding errors in Oracle when inserting into BLOB columns.
     *
     * @since 2.2
     */
    public function dbTypecast($value)
    {
        if ($this->type === Schema::TYPE_BINARY && $this->dbType === 'BLOB') {
            if ($value instanceof PdoValue) {
                return parent::dbTypecast($value);
            }

            if (is_string($value)) {
                $placeholder = 'qp' . str_replace('.', '', uniqid('', true));

                return new Expression(
                    "TO_BLOB(UTL_RAW.CAST_TO_RAW(:{$placeholder}))",
                    [":{$placeholder}" => $value]
                );
            }
        }

        return parent::dbTypecast($value);
    }

    /**
     * Converts an Oracle column default value to its PHP representation.
     *
     * Handles Oracle-specific default value formats:
     * - `null`, empty string, or `'NULL'` → `null`.
     * - `CURRENT_TIMESTAMP` on timestamp columns → `Expression('CURRENT_TIMESTAMP')`.
     * - Server-managed timestamp defaults (`SYSTIMESTAMP`, `TIMESTAMP 'literal'`, etc.) → `null`.
     * - Single-quote-wrapped string defaults (`'value'`) → unwrapped value.
     * - Everything else → delegates to `$this->phpTypecast()`.
     *
     * @param mixed $value default value in Oracle format.
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

        if ($value === '' || $value === 'NULL') {
            return null;
        }

        // Must check CURRENT_TIMESTAMP before the broader 'timestamp' keyword check.
        if ($this->type === Schema::TYPE_TIMESTAMP && strcasecmp($value, 'CURRENT_TIMESTAMP') === 0) {
            return new Expression('CURRENT_TIMESTAMP');
        }

        // Server-managed timestamp defaults: SYSTIMESTAMP, TIMESTAMP 'literal', LOCALTIMESTAMP, etc.
        if ($this->type === Schema::TYPE_TIMESTAMP && stripos($value, 'timestamp') !== false) {
            return null;
        }

        // Strip single-quote wrappers from string defaults: 'value' → value
        if (strlen($value) > 2 && $value[0] === "'" && $value[-1] === "'") {
            $value = substr($value, 1, -1);
        }

        return parent::defaultPhpTypecast($value);
    }
}
