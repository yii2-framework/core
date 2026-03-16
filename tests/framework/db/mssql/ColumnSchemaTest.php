<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use yii\db\Expression;
use yii\db\mssql\ColumnSchema;
use yiiunit\framework\db\mssql\providers\ColumnSchemaProvider;

/**
 * Unit tests for {@see ColumnSchema} with MSSQL driver.
 *
 * {@see ColumnSchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mssql')]
#[Group('column-schema')]
final class ColumnSchemaTest extends TestCase
{
    #[DataProviderExternal(ColumnSchemaProvider::class, 'dbTypecast')]
    public function testDbTypecast(
        string $type,
        string $dbType,
        bool $allowNull,
        mixed $value,
        mixed $expected,
    ): void {
        $column = new ColumnSchema();

        $column->type = $type;
        $column->dbType = $dbType;
        $column->allowNull = $allowNull;

        $result = $column->dbTypecast($value);

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

    #[DataProviderExternal(ColumnSchemaProvider::class, 'defaultPhpTypecast')]
    public function testDefaultPhpTypecast(string $type, mixed $value, mixed $expected): void
    {
        $column = new ColumnSchema();

        $column->type = $type;
        $column->phpType = match ($type) {
            'integer' => 'integer',
            default => 'string',
        };

        $result = $column->defaultPhpTypecast($value);

        self::assertSame(
            $expected,
            $result,
            'Result does not match expected value.',
        );
    }

    #[DataProviderExternal(ColumnSchemaProvider::class, 'getOutputColumnDeclaration')]
    public function testGetOutputColumnDeclaration(
        string $dbType,
        bool $allowNull,
        int|null $size,
        string $expected,
    ): void {
        $column = new ColumnSchema();

        $column->dbType = $dbType;
        $column->allowNull = $allowNull;
        $column->size = $size;

        self::assertSame(
            $expected,
            $column->getOutputColumnDeclaration(),
            'Result does not match expected SQL type.',
        );
    }
}
