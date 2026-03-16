<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql\providers;

use yii\db\Expression;

/**
 * Data provider for {@see \yiiunit\framework\db\pgsql\ColumnSchemaTest} test cases.
 *
 * Provides representative input/output pairs for the `dbTypecast()`, `defaultPhpTypecast()`, and `phpTypecast()`
 * methods.
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
            "binary bit B'10101'" => [
                'integer',
                'bit',
                'integer',
                "B'10101'::bit(5)",
                21,
            ],
            'boolean false' => [
                'boolean',
                'bool',
                'boolean',
                'false',
                false,
            ],
            "boolean false cast 'false'::boolean" => [
                'boolean',
                'bool',
                'boolean',
                "'false'::boolean",
                false,
            ],
            'boolean true' => [
                'boolean',
                'bool',
                'boolean',
                'true',
                true,
            ],
            "boolean true cast 'true'::boolean" => [
                'boolean',
                'bool',
                'boolean',
                "'true'::boolean",
                true,
            ],
            'cast notation integer' => [
                'integer',
                'int4',
                'integer',
                "'42'::integer",
                42,
            ],
            'cast notation string' => [
                'string',
                'varchar',
                'string',
                "'hello'::character varying",
                'hello',
            ],
            'complex timezone expression' => [
                'timestamp',
                'timestamp',
                'string',
                "timezone('UTC'::text, '1970-01-01 00:00:00+00'::timestamp with time zone)",
                new Expression("timezone('UTC'::text, '1970-01-01 00:00:00+00'::timestamp with time zone)"),
            ],
            'CURRENT_DATE on date' => [
                'date',
                'date',
                'string',
                'CURRENT_DATE',
                new Expression('CURRENT_DATE'),
            ],
            'CURRENT_TIME on time' => [
                'time',
                'time',
                'string',
                'CURRENT_TIME',
                new Expression('CURRENT_TIME'),
            ],
            'CURRENT_TIMESTAMP on timestamp' => [
                'timestamp',
                'timestamp',
                'string',
                'CURRENT_TIMESTAMP',
                new Expression('CURRENT_TIMESTAMP'),
            ],
            'now() on timestamp' => [
                'timestamp',
                'timestamp',
                'string',
                'now()',
                new Expression('now()'),
            ],
            'NOW() on timestamp' => [
                'timestamp',
                'timestamp',
                'string',
                'NOW()',
                new Expression('NOW()'),
            ],
            'null returns null' => [
                'timestamp',
                'timestamp',
                'string',
                null,
                null,
            ],
            'parenthesized NULL' => [
                'string',
                'varchar',
                'string',
                '(NULL)::character varying',
                null,
            ],
            'parenthesized numeric' => [
                'decimal',
                'numeric',
                'string',
                '(0)::numeric',
                '0',
            ],
            'quoted bit' => [
                'integer',
                'bit',
                'integer',
                "'101'::\"bit\"",
                5,
            ],
            'regular integer' => [
                'integer',
                'int4',
                'integer',
                '42',
                42,
            ],
            'regular string' => [
                'string',
                'varchar',
                'string',
                'hello',
                'hello',
            ],
            'timezone expression' => [
                'timestamp',
                'timestamp',
                'string',
                "timezone('UTC'::text, now())",
                new Expression("timezone('UTC'::text, now())"),
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string, string, mixed, mixed}>
     */
    public static function phpTypecast(): array
    {
        return [
            'boolean native false returns false' => [
                'boolean',
                'bool',
                'boolean',
                false,
                false,
            ],
            'boolean native true returns true' => [
                'boolean',
                'bool',
                'boolean',
                true,
                true,
            ],
            'boolean other value casts to bool' => [
                'boolean',
                'bool',
                'boolean',
                '1',
                true,
            ],
            'boolean string f returns false' => [
                'boolean',
                'bool',
                'boolean',
                'f',
                false,
            ],
            'boolean string false returns false' => [
                'boolean',
                'bool',
                'boolean',
                'false',
                false,
            ],
            'boolean string t returns true' => [
                'boolean',
                'bool',
                'boolean',
                't',
                true,
            ],
            'boolean string true returns true' => [
                'boolean',
                'bool',
                'boolean',
                'true',
                true,
            ],
            'integer fallback to parent' => [
                'integer',
                'int4',
                'integer',
                '42',
                42,
            ],
            'json decodes to array' => [
                'json',
                'json',
                'string',
                '{"a":1}',
                ['a' => 1],
            ],
            'null returns null' => [
                'boolean',
                'bool',
                'boolean',
                null,
                null,
            ],
            'string fallback to parent' => [
                'string',
                'varchar',
                'string',
                'hello',
                'hello',
            ],
        ];
    }
}
