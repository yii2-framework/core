<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql\providers;

use yii\db\Expression;

/**
 * Data provider for {@see \yiiunit\framework\db\mysql\ColumnSchemaTest} test cases.
 *
 * Provides representative input/output pairs for the `defaultPhpTypecast()` method.
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
            'bit default b\'1\'' => [
                'boolean',
                'bit(1)',
                'boolean',
                "b'1'",
                1,
            ],
            'bit default b\'10101\'' => [
                'integer',
                'bit(5)',
                'integer',
                "b'10101'",
                21,
            ],
            'current_timestamp() on timestamp column (MariaDB format)' => [
                'timestamp',
                'timestamp',
                'string',
                'current_timestamp()',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'CURRENT_TIMESTAMP on date column' => [
                'date',
                'date',
                'string',
                'CURRENT_TIMESTAMP',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'CURRENT_TIMESTAMP(3) on datetime column' => [
                'datetime',
                'datetime',
                'string',
                'CURRENT_TIMESTAMP(3)',
                new Expression('CURRENT_TIMESTAMP(3)'),
            ],
            'CURRENT_TIMESTAMP on time column' => [
                'time',
                'time',
                'string',
                'CURRENT_TIMESTAMP',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'CURRENT_TIMESTAMP on timestamp column' => [
                'timestamp',
                'timestamp',
                'string',
                'CURRENT_TIMESTAMP',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'CURRENT_TIMESTAMP(0) on timestamp column preserves precision' => [
                'timestamp',
                'timestamp',
                'string',
                'CURRENT_TIMESTAMP(0)',
                new Expression('CURRENT_TIMESTAMP(0)'),
            ],
            'JSON default decodes to PHP array' => [
                'json',
                'json',
                'string',
                '{"key":"value"}',
                ['key' => 'value'],
            ],
            'null value returns null' => [
                'timestamp',
                'timestamp',
                'string',
                null,
                null,
            ],
            'regular integer default' => [
                'integer',
                'int',
                'integer',
                '42',
                42,
            ],
            'regular string default' => [
                'string',
                'varchar(255)',
                'string',
                'hello',
                'hello',
            ],
        ];
    }
}
