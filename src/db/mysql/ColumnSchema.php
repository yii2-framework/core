<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yii\db\mysql;

use yii\db\Expression;
use yii\db\ExpressionInterface;
use yii\db\JsonExpression;

use function in_array;
use function is_string;
use function str_replace;
use function strpos;
use function strrpos;
use function substr;

/**
 * Represents the metadata of a column in a MySQL database table.
 *
 * Extends the base {@see \yii\db\ColumnSchema} with MySQL-specific type handling:
 * - Converts `json` values to {@see JsonExpression} for safe binding.
 * - Normalizes `CURRENT_TIMESTAMP` defaults on temporal columns to {@see Expression} instances.
 * - Converts bit defaults (`b'...'`) to their integer representation via `bindec()`.
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 * @since 2.0.14.1
 */
class ColumnSchema extends \yii\db\ColumnSchema
{
    /**
     * @var bool whether the column schema should OMIT using JSON support feature.
     * You can use this property to make upgrade to Yii 2.0.14 easier.
     * Default to `false`, meaning JSON support is enabled.
     *
     * @since 2.0.14.1
     * @deprecated Since 2.0.14.1 and will be removed in 2.1.
     */
    public $disableJsonSupport = false;


    /**
     * {@inheritdoc}
     *
     * Extends the base implementation to parse MySQL enum literals from `dbType` using a quote-aware parser that
     * handles values containing parentheses (`'a)'`) and escaped single quotes (`'it''s'`).
     */
    public function extractSizeFromDbType(): string
    {
        $this->enumValues = null;

        $type = parent::extractSizeFromDbType();

        if (strtolower($type) === 'enum') {
            $openPos = strpos($this->dbType, '(');
            $closePos = strrpos($this->dbType, ')');

            if ($openPos !== false && $closePos !== false && $closePos > $openPos) {
                $payload = substr($this->dbType, $openPos + 1, $closePos - $openPos - 1);

                if (preg_match_all("/'(?:[^']*(?:''[^']*)*)'/", $payload, $matches)) {
                    $this->enumValues = [];

                    foreach ($matches[0] as $value) {
                        $this->enumValues[] = str_replace("''", "'", substr($value, 1, -1));
                    }
                }
            }
        }

        return $type;
    }

    /**
     * {@inheritdoc}
     */
    public function dbTypecast($value)
    {
        if ($value === null) {
            return $value;
        }

        if ($value instanceof ExpressionInterface) {
            return $value;
        }

        if (!$this->disableJsonSupport && $this->dbType === Schema::TYPE_JSON) {
            return new JsonExpression($value, $this->type);
        }

        return $this->typecast($value);
    }

    /**
     * {@inheritdoc}
     */
    public function phpTypecast($value)
    {
        if ($value === null) {
            return null;
        }

        if (!$this->disableJsonSupport && $this->type === Schema::TYPE_JSON) {
            return json_decode($value, true);
        }

        return parent::phpTypecast($value);
    }

    /**
     * Converts a MySQL column default value to its PHP representation.
     *
     * Handles MySQL-specific default value formats:
     * - `null` → `null`.
     * - `CURRENT_TIMESTAMP` / `current_timestamp()` on temporal columns (`timestamp`, `datetime`, `date`, `time`)
     *   → `Expression('CURRENT_TIMESTAMP')` or `Expression('CURRENT_TIMESTAMP(N)')`.
     * - `b'...'` bit defaults when `$this->dbType` starts with `bit` → integer via `bindec()`.
     * - Everything else → delegates to `$this->phpTypecast()`.
     *
     * @param mixed $value default value in MySQL format.
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

        if (
            is_string($value)
            && in_array($this->type, ['timestamp', 'datetime', 'date', 'time'], true)
            && preg_match('/^current_timestamp(?:\(([0-9]*)\))?$/i', $value, $matches)
        ) {
            $precision = $matches[1] ?? '';

            return new Expression('CURRENT_TIMESTAMP' . ($precision !== '' ? "({$precision})" : ''));
        }

        if (is_string($value) && strncasecmp($this->dbType, 'bit', 3) === 0) {
            return bindec(trim($value, "b'"));
        }

        return $this->phpTypecast($value);
    }
}
