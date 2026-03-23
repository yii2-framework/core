<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite\providers;

use yii\db\Expression;

/**
 * Data provider for {@see \yiiunit\framework\db\sqlite\ColumnSchemaTest} test cases.
 *
 * Provides representative input/output pairs for the SQLite `defaultPhpTypecast()` method, including BIT size-range
 * boundary cases (`boolean`, `smallint`, `integer`, `bigint`).
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class ColumnSchemaProvider
{
    /**
     * @phpstan-return array<string, array{string, string, string, mixed, mixed}>
     */
    public static function defaultPhpTypecast(): array
    {
        return [
            'bit(1) maps to boolean' => [
                'boolean',
                'bit(1)',
                'boolean',
                '1',
                true,
            ],
            'bit(5) maps to smallint' => [
                'smallint',
                'bit(5)',
                'integer',
                '21',
                21,
            ],
            'bit(16) maps to smallint (upper boundary)' => [
                'smallint',
                'bit(16)',
                'integer',
                '65535',
                65535,
            ],
            'bit(17) maps to integer (lower boundary)' => [
                'integer',
                'bit(17)',
                'integer',
                '131071',
                131071,
            ],
            'bit(32) maps to integer (upper boundary)' => [
                'integer',
                'bit(32)',
                'integer',
                '2147483649',
                2147483649,
            ],
            'bit(33) maps to bigint' => [
                'bigint',
                'bit(33)',
                'integer',
                '4294967297',
                4294967297,
            ],
            'boolean false default (0)' => [
                'boolean',
                'tinyint(1)',
                'boolean',
                '0',
                false,
            ],
            'boolean true default (1)' => [
                'boolean',
                'tinyint(1)',
                'boolean',
                '1',
                true,
            ],
            'CURRENT_TIMESTAMP on non-timestamp column passes through as string' => [
                'string',
                'varchar',
                'string',
                'CURRENT_TIMESTAMP',
                'CURRENT_TIMESTAMP',
            ],
            'CURRENT_TIMESTAMP on timestamp column returns Expression' => [
                'timestamp',
                'timestamp',
                'string',
                'CURRENT_TIMESTAMP',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'decimal default' => [
                'decimal',
                'decimal(10,2)',
                'string',
                '3.14',
                '3.14',
            ],
            'double default' => [
                'double',
                'double',
                'double',
                '1.5',
                1.5,
            ],
            'double-quoted string default is unwrapped' => [
                'string',
                'varchar',
                'string',
                '"hello"',
                'hello',
            ],
            'empty string returns null' => [
                'string',
                'varchar',
                'string',
                '',
                null,
            ],
            'integer default' => [
                'integer',
                'integer',
                'integer',
                '42',
                42,
            ],
            'null literal returns null' => [
                'string',
                'varchar',
                'string',
                'null',
                null,
            ],
            'null value returns null' => [
                'timestamp',
                'timestamp',
                'string',
                null,
                null,
            ],
            'single-quoted string default is unwrapped' => [
                'string',
                'varchar',
                'string',
                "'hello'",
                'hello',
            ],
        ];
    }
}
