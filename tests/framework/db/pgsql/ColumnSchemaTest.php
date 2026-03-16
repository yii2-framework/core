<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use yii\db\ArrayExpression;
use yii\db\Expression;
use yii\db\JsonExpression;
use yii\db\pgsql\ColumnSchema;
use yiiunit\framework\db\pgsql\providers\ColumnSchemaProvider;

/**
 * Unit tests for {@see ColumnSchema} with PostgreSQL driver.
 *
 * Test coverage.
 * - Converts array dimension values to {@see ArrayExpression} or raw arrays depending on configuration.
 * - Converts JSON/JSONB values to {@see JsonExpression} for safe binding.
 * - Ensures `dbTypecast()` handles `null`, {@see Expression}, array dimensions, JSON/JSONB, and scalar fallback.
 * - Ensures `defaultPhpTypecast()` handles all PostgreSQL default value formats.
 * - Ensures `phpTypecast()` handles boolean, JSON, array, and scalar values.
 * - Passes through disabled JSON support returning raw string values.
 *
 * {@see ColumnSchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('pgsql')]
#[Group('column-schema')]
final class ColumnSchemaTest extends TestCase
{
    public function testDbTypecastNullReturnsNull(): void
    {
        $column = $this->createColumn('string', 'varchar', 'string');

        self::assertNull($column->dbTypecast(null));
    }

    public function testDbTypecastExpressionPassesThrough(): void
    {
        $column = $this->createColumn('string', 'varchar', 'string');
        $expression = new Expression('NOW()');

        self::assertSame($expression, $column->dbTypecast($expression));
    }

    public function testDbTypecastArrayDimensionWithSupport(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');
        $column->dimension = 1;

        $result = $column->dbTypecast([1, 2, 3]);

        self::assertInstanceOf(ArrayExpression::class, $result);
        self::assertSame([1, 2, 3], $result->getValue());
        self::assertSame('int4', $result->getType());
        self::assertSame(1, $result->getDimension());
    }

    public function testDbTypecastArrayDimensionWithoutSupport(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');
        $column->dimension = 1;
        $column->disableArraySupport = true;

        self::assertSame('{1,2,3}', $column->dbTypecast('{1,2,3}'));
    }

    public function testDbTypecastJsonReturnsJsonExpression(): void
    {
        $column = $this->createColumn('json', 'json', 'string');

        $result = $column->dbTypecast(['key' => 'value']);

        self::assertInstanceOf(JsonExpression::class, $result);
        self::assertSame(['key' => 'value'], $result->getValue());
        self::assertSame('json', $result->getType());
    }

    public function testDbTypecastJsonbReturnsJsonExpression(): void
    {
        $column = $this->createColumn('json', 'jsonb', 'string');

        $result = $column->dbTypecast(['key' => 'value']);

        self::assertInstanceOf(JsonExpression::class, $result);
        self::assertSame(['key' => 'value'], $result->getValue());
        self::assertSame('jsonb', $result->getType());
    }

    public function testDbTypecastJsonDisabledFallsThrough(): void
    {
        $column = $this->createColumn('string', 'json', 'string');
        $column->disableJsonSupport = true;

        self::assertSame('test', $column->dbTypecast('test'));
    }

    public function testDbTypecastRegularValueFallsThrough(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        self::assertSame(42, $column->dbTypecast('42'));
    }

    #[DataProviderExternal(ColumnSchemaProvider::class, 'defaultPhpTypecast')]
    public function testDefaultPhpTypecast(
        string $type,
        string $dbType,
        string $phpType,
        mixed $value,
        mixed $expected,
    ): void {
        $column = $this->createColumn($type, $dbType, $phpType);

        $result = $column->defaultPhpTypecast($value);

        if ($expected instanceof Expression) {
            self::assertInstanceOf(Expression::class, $result, 'Should return an Expression instance.');
            self::assertSame($expected->expression, $result->expression, 'Expression SQL does not match.');
        } else {
            self::assertSame($expected, $result, 'Result does not match expected value.');
        }
    }

    #[DataProviderExternal(ColumnSchemaProvider::class, 'phpTypecast')]
    public function testPhpTypecast(
        string $type,
        string $dbType,
        string $phpType,
        mixed $value,
        mixed $expected,
    ): void {
        $column = $this->createColumn($type, $dbType, $phpType);

        self::assertSame($expected, $column->phpTypecast($value));
    }

    public function testPhpTypecastJsonDisabledReturnsRaw(): void
    {
        $column = $this->createColumn('json', 'json', 'string');
        $column->disableJsonSupport = true;

        self::assertSame('{"a":1}', $column->phpTypecast('{"a":1}'));
    }

    public function testPhpTypecastArrayDisabledReturnsRaw(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');
        $column->dimension = 1;
        $column->disableArraySupport = true;

        self::assertSame('{1,2,3}', $column->phpTypecast('{1,2,3}'));
    }

    public function testPhpTypecastArrayStringParsesToArrayExpression(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');
        $column->dimension = 1;

        $result = $column->phpTypecast('{1,2,3}');

        self::assertInstanceOf(ArrayExpression::class, $result);
        self::assertSame([1, 2, 3], $result->getValue());
    }

    public function testPhpTypecastArrayInputWalksToArrayExpression(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');
        $column->dimension = 1;

        $result = $column->phpTypecast(['1', '2', '3']);

        self::assertInstanceOf(ArrayExpression::class, $result);
        self::assertSame([1, 2, 3], $result->getValue());
    }

    public function testPhpTypecastArrayNullReturnsNull(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');
        $column->dimension = 1;

        self::assertNull($column->phpTypecast(null));
    }

    public function testPhpTypecastArrayNoDeserializeReturnsRawArray(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');
        $column->dimension = 1;
        $column->deserializeArrayColumnToArrayExpression = false;

        self::assertSame([1, 2, 3], $column->phpTypecast('{1,2,3}'));
    }

    private function createColumn(string $type, string $dbType, string $phpType): ColumnSchema
    {
        $column = new ColumnSchema();

        $column->type = $type;
        $column->dbType = $dbType;
        $column->phpType = $phpType;

        return $column;
    }
}
