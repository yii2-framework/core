<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\providers;

/**
 * Data provider for {@see \yiiunit\framework\db\ColumnSchemaTest} test cases.
 *
 * Provides representative input/output pairs for the base `defaultPhpTypecast()` method.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class ColumnSchemaProvider
{
    /**
     * @phpstan-return array<string, array{string, string, mixed, mixed}>
     */
    public static function defaultPhpTypecast(): array
    {
        return [
            'boolean true string' => [
                'boolean',
                'boolean',
                '1',
                true,
            ],
            'double string is cast to float' => [
                'double',
                'double',
                '3.14',
                3.14,
            ],
            'integer string is cast to integer' => [
                'integer',
                'integer',
                '42',
                42,
            ],
            'null value returns null' => [
                'string',
                'string',
                null,
                null,
            ],
            'string value passes through' => [
                'string',
                'string',
                'hello',
                'hello',
            ],
        ];
    }
}
