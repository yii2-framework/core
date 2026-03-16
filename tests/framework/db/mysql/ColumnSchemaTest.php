<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;
use yii\db\mysql\ColumnSchema;
use yiiunit\framework\db\mysql\providers\ColumnSchemaProvider;

/**
 * Unit tests for {@see ColumnSchema} with MySQL driver.
 *
 * {@see ColumnSchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mysql')]
#[Group('column-schema')]
final class ColumnSchemaTest extends TestCase
{
    #[DataProviderExternal(ColumnSchemaProvider::class, 'defaultPhpTypecast')]
    public function testDefaultPhpTypecast(
        string $type,
        string $dbType,
        string $phpType,
        mixed $value,
        mixed $expected,
    ): void {
        $column = new ColumnSchema();

        $column->type = $type;
        $column->dbType = $dbType;
        $column->phpType = $phpType;

        $result = $column->defaultPhpTypecast($value);

        if (!($expected instanceof Expression)) {
            self::assertSame(
                $expected,
                $result,
                'Result does not match expected value.',
            );

            return;
        }

        self::assertInstanceOf(
            Expression::class,
            $result,
            'Should return an Expression instance.',
        );
        self::assertSame(
            $expected->expression,
            $result->expression,
            'Expression SQL does not match.',
        );
    }
}
