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
 * Provides representative input/output pairs for the SQLite `defaultPhpTypecast()` method.
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
