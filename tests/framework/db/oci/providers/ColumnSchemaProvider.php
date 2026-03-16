<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci\providers;

use yii\db\Expression;
use yii\db\oci\Schema;

/**
 * Data provider for {@see \yiiunit\framework\db\oci\ColumnSchemaTest} test cases.
 *
 * Provides representative input/output pairs for Oracle `ColumnSchema` methods: `dbTypecast()` and
 * `defaultPhpTypecast()`.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class ColumnSchemaProvider
{
    /**
     * @phpstan-return array<string, array{string, string, mixed, mixed}>
     */
    public static function dbTypecast(): array
    {
        return [
            'BLOB integer value falls through to parent' => [
                Schema::TYPE_BINARY,
                'BLOB',
                123,
                123,
            ],
            'BLOB null value falls through to parent' => [
                Schema::TYPE_BINARY,
                'BLOB',
                null,
                null,
            ],
            'BLOB string value returns Expression' => [
                Schema::TYPE_BINARY,
                'BLOB',
                'binary data',
                Expression::class,
            ],
            'non-BLOB string falls through to parent' => [
                Schema::TYPE_STRING,
                'VARCHAR2',
                'test',
                'test',
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string, string, mixed, mixed}>
     */
    public static function defaultPhpTypecast(): array
    {
        return [
            'CURRENT_TIMESTAMP on non-timestamp column passes through as string' => [
                'string',
                'VARCHAR2',
                'string',
                'CURRENT_TIMESTAMP',
                'CURRENT_TIMESTAMP',
            ],
            'CURRENT_TIMESTAMP on timestamp column returns Expression' => [
                'timestamp',
                'TIMESTAMP(6)',
                'string',
                'CURRENT_TIMESTAMP',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'CURRENT_TIMESTAMP with leading/trailing spaces on timestamp column' => [
                'timestamp',
                'TIMESTAMP(6)',
                'string',
                '  CURRENT_TIMESTAMP  ',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'decimal default' => [
                'decimal',
                'NUMBER',
                'string',
                '3.14',
                '3.14',
            ],
            'empty string returns null' => [
                'string',
                'VARCHAR2',
                'string',
                '',
                null,
            ],
            'null value returns null' => [
                'timestamp',
                'TIMESTAMP(6)',
                'string',
                null,
                null,
            ],
            'NULL literal returns null' => [
                'string',
                'VARCHAR2',
                'string',
                'NULL',
                null,
            ],
            'quoted string containing timestamp keyword is not nullified' => [
                'string',
                'VARCHAR2',
                'string',
                "'update_timestamp_flag'",
                'update_timestamp_flag',
            ],
            'regular integer default' => [
                'integer',
                'NUMBER',
                'integer',
                '42',
                42,
            ],
            'regular string default without quotes' => [
                'string',
                'VARCHAR2',
                'string',
                'hello',
                'hello',
            ],
            'single-quoted string default is unwrapped' => [
                'string',
                'CHAR',
                'string',
                "'1'",
                '1',
            ],
            'SYSTIMESTAMP returns null' => [
                'timestamp',
                'TIMESTAMP(6)',
                'string',
                'SYSTIMESTAMP',
                null,
            ],
            'TIMESTAMP literal returns null' => [
                'timestamp',
                'TIMESTAMP(6)',
                'string',
                "TIMESTAMP '2002-01-01 00:00:00'",
                null,
            ],
            'whitespace-only value returns null (trimmed to empty)' => [
                'string',
                'VARCHAR2',
                'string',
                '   ',
                null,
            ],
        ];
    }
}
