<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use yii\db\ColumnSchema;
use yiiunit\framework\db\providers\ColumnSchemaProvider;

/**
 * Unit tests for the base {@see ColumnSchema} class.
 *
 * {@see ColumnSchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('column-schema')]
final class ColumnSchemaTest extends TestCase
{
    #[DataProviderExternal(ColumnSchemaProvider::class, 'defaultPhpTypecast')]
    public function testDefaultPhpTypecast(
        string $type,
        string $phpType,
        mixed $value,
        mixed $expected,
    ): void {
        $column = new ColumnSchema();

        $column->type = $type;
        $column->phpType = $phpType;

        self::assertSame(
            $expected,
            $column->defaultPhpTypecast($value),
            'Base defaultPhpTypecast() should delegate to phpTypecast().',
        );
    }
}
