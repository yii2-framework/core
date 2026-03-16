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

        self::assertNull(
            $column->dbTypecast(null),
            "Should return 'null'.",
        );
    }

    public function testDbTypecastExpressionPassesThrough(): void
    {
        $column = $this->createColumn('string', 'varchar', 'string');

        $expression = new Expression('NOW()');

        self::assertSame(
            $expression,
            $column->dbTypecast($expression),
            'Expression should pass through unchanged.',
        );
    }

    public function testDbTypecastArrayDimensionWithSupport(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        $column->dimension = 1;

        $result = $column->dbTypecast([1, 2, 3]);

        self::assertInstanceOf(
            ArrayExpression::class,
            $result,
            'Should return an ArrayExpression instance.',
        );
        self::assertSame(
            [1, 2, 3],
            $result->getValue(),
            'ArrayExpression value does not match.',
        );
        self::assertSame(
            'int4',
            $result->getType(),
            'ArrayExpression type does not match.',
        );
        self::assertSame(
            1,
            $result->getDimension(),
            'ArrayExpression dimension does not match.',
        );
    }

    public function testDbTypecastArrayDimensionWithoutSupport(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        $column->dimension = 1;
        $column->disableArraySupport = true;

        self::assertSame(
            '{1,2,3}',
            $column->dbTypecast('{1,2,3}'),
            "Should cast to string when 'array' support is disabled.",
        );
    }

    public function testDbTypecastJsonReturnsJsonExpression(): void
    {
        $column = $this->createColumn('json', 'json', 'string');

        $result = $column->dbTypecast(['key' => 'value']);

        self::assertInstanceOf(
            JsonExpression::class,
            $result,
            'Should return a JsonExpression instance.',
        );
        self::assertSame(
            ['key' => 'value'],
            $result->getValue(),
            'JsonExpression value does not match.',
        );
        self::assertSame(
            'json',
            $result->getType(),
            'JsonExpression type does not match.',
        );
    }

    public function testDbTypecastJsonbReturnsJsonExpression(): void
    {
        $column = $this->createColumn('json', 'jsonb', 'string');

        $result = $column->dbTypecast(['key' => 'value']);

        self::assertInstanceOf(
            JsonExpression::class,
            $result,
            'Should return a JsonExpression instance.',
        );
        self::assertSame(
            ['key' => 'value'],
            $result->getValue(),
            'JsonExpression value does not match.',
        );
        self::assertSame(
            'jsonb',
            $result->getType(),
            'JsonExpression type does not match.',
        );
    }

    public function testDbTypecastJsonDisabledFallsThrough(): void
    {
        $column = $this->createColumn('string', 'json', 'string');

        $column->disableJsonSupport = true;

        self::assertSame(
            'test',
            $column->dbTypecast('test'),
            'Should fall through to typecast when JSON support is disabled.',
        );
    }

    public function testDbTypecastRegularValueFallsThrough(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        self::assertSame(
            42,
            $column->dbTypecast('42'),
            "Should typecast regular value to 'integer'.",
        );
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

    #[DataProviderExternal(ColumnSchemaProvider::class, 'phpTypecast')]
    public function testPhpTypecast(
        string $type,
        string $dbType,
        string $phpType,
        mixed $value,
        mixed $expected,
    ): void {
        $column = $this->createColumn($type, $dbType, $phpType);

        self::assertSame(
            $expected,
            $column->phpTypecast($value),
            'Result does not match expected value.',
        );
    }

    public function testPhpTypecastJsonDisabledReturnsRaw(): void
    {
        $column = $this->createColumn('json', 'json', 'string');

        $column->disableJsonSupport = true;

        self::assertSame(
            '{"a":1}',
            $column->phpTypecast('{"a":1}'),
            'Should return raw JSON string when support is disabled.',
        );
    }

    public function testPhpTypecastArrayDisabledReturnsRaw(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        $column->dimension = 1;
        $column->disableArraySupport = true;

        self::assertSame(
            '{1,2,3}',
            $column->phpTypecast('{1,2,3}'),
            "Should return raw value when 'array' support is disabled.",
        );
    }

    public function testPhpTypecastArrayStringParsesToArrayExpression(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        $column->dimension = 1;

        $result = $column->phpTypecast('{1,2,3}');

        self::assertInstanceOf(
            ArrayExpression::class,
            $result,
            'Should return an ArrayExpression instance.',
        );
        self::assertSame(
            [1, 2, 3],
            $result->getValue(),
            "Parsed 'array' values do not match.",
        );
    }

    public function testPhpTypecastArrayInputWalksToArrayExpression(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        $column->dimension = 1;

        $result = $column->phpTypecast(['1', '2', '3']);

        self::assertInstanceOf(
            ArrayExpression::class,
            $result,
            'Should return an ArrayExpression instance.',
        );
        self::assertSame(
            [1, 2, 3],
            $result->getValue(),
            'Array values should be typecast to integers.',
        );
    }

    public function testPhpTypecastArrayNullReturnsNull(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        $column->dimension = 1;

        self::assertNull(
            $column->phpTypecast(null),
            "Should return 'null' for array column.",
        );
    }

    public function testPhpTypecastArrayNoDeserializeReturnsRawArray(): void
    {
        $column = $this->createColumn('integer', 'int4', 'integer');

        $column->dimension = 1;
        $column->deserializeArrayColumnToArrayExpression = false;

        self::assertSame(
            [1, 2, 3],
            $column->phpTypecast('{1,2,3}'),
            "Should return raw 'array' when deserialization is disabled.",
        );
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
