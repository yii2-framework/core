<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yii\db\sqlite\Schema;
use yiiunit\base\db\BaseSchema;
use yiiunit\framework\db\sqlite\providers\SchemaProvider;

/**
 * Unit test for {@see \yii\db\sqlite\Schema} with SQLite driver.
 *
 * {@see SchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('sqlite')]
#[Group('schema')]
final class SchemaTest extends BaseSchema
{
    protected $driverName = 'sqlite';

    #[DataProviderExternal(SchemaProvider::class, 'unquoteSimpleTableName')]
    public function testUnquoteSimpleTableName(string $input, string $expected): void
    {
        parent::testUnquoteSimpleTableName($input, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'unquoteSimpleColumnName')]
    public function testUnquoteSimpleColumnName(string $input, string $expected): void
    {
        parent::testUnquoteSimpleColumnName($input, $expected);
    }

    #[DataProviderExternal(SchemaProvider::class, 'expectedColumns')]
    public function testColumnSchema(array $columns): void
    {
        parent::testColumnSchema($columns);
    }

    public function testGetSchemaNames(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(Schema::class . ' does not support fetching all schema names.');

        parent::testGetSchemaNames();
    }

    public function testNegativeDefaultValues(): void
    {
        $schema = $this->getConnection(false, true)->schema;

        $table = $schema->getTableSchema('negative_default_values');

        self::assertSame(
            -123,
            $table->getColumn('tinyint_col')->defaultValue,
            "'tinyint_col' default does not match.",
        );
        self::assertSame(
            -123,
            $table->getColumn('smallint_col')->defaultValue,
            "'smallint_col' default does not match.",
        );
        self::assertSame(
            -123,
            $table->getColumn('int_col')->defaultValue,
            "'int_col' default does not match.",
        );
        self::assertSame(
            -123,
            $table->getColumn('bigint_col')->defaultValue,
            "'bigint_col' default does not match.",
        );
        self::assertSame(
            -12345.6789,
            $table->getColumn('float_col')->defaultValue,
            "'float_col' default does not match.",
        );
        self::assertSame(
            '-33.22',
            $table->getColumn('numeric_col')->defaultValue,
            "'numeric_col' default does not match.",
        );
    }

    public function testCompositeFk(): void
    {
        $schema = $this->getConnection(false, true)->schema;

        $table = $schema->getTableSchema('composite_fk');

        self::assertCount(
            1,
            $table->foreignKeys, 'Composite FK table should have exactly one foreign key.',
        );
        self::assertTrue(
            isset($table->foreignKeys[0]),
            "Foreign key at index '0' should exist.",
        );
        self::assertEquals(
            'order_item',
            $table->foreignKeys[0][0],
            "Foreign key should reference the 'order_item' table.",
        );
        self::assertEquals(
            'order_id',
            $table->foreignKeys[0]['order_id'],
            "Foreign key column 'order_id' should map correctly.",
        );
        self::assertEquals(
            'item_id',
            $table->foreignKeys[0]['item_id'],
            "Foreign key column 'item_id' should map correctly.",
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->getConnection(false, true);

        $constraints = $db->schema->{'getTable' . ucfirst($type)}($tableName);

        self::assertMetadataEquals($expected, $constraints);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->getConnection(false, true);

        $db->getSlavePdo(true)->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $constraints = $db->schema->{'getTable' . ucfirst($type)}($tableName, true);

        self::assertMetadataEquals($expected, $constraints);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->getConnection(false, true);

        $db->getSlavePdo(true)->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $constraints = $db->schema->{'getTable' . ucfirst($type)}($tableName, true);

        self::assertMetadataEquals($expected, $constraints);
    }

    #[DataProviderExternal(SchemaProvider::class, 'quoteTableName')]
    public function testQuoteTableName(string $name, string $expectedName): void
    {
        $db = $this->getConnection(false, true);

        $quotedName = $db->schema->quoteTableName($name);

        self::assertEquals(
            $expectedName,
            $quotedName,
            "Quoting table name '$name' does not match expected.",
        );
    }

    public function testThrowNotSupportedExceptionWhenTableSchemaConstraintsDefaultValues(): void
    {
        $db = $this->getConnection(false, true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('SQLite does not support default value constraints.');

        $db->schema->getTableDefaultValues('T_constraints_1');
    }
}
