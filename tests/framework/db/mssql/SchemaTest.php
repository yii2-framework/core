<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\db\mssql\Schema;
use yiiunit\base\db\BaseSchema;
use yiiunit\framework\db\mssql\providers\SchemaProvider;

/**
 * Unit test for {@see \yii\db\mssql\Schema} with MSSQL driver.
 *
 * {@see SchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mssql')]
#[Group('schema')]
final class SchemaTest extends BaseSchema
{
    public $driverName = 'sqlsrv';

    protected $expectedSchemas = [
        'dbo',
    ];

    #[DataProviderExternal(SchemaProvider::class, 'expectedColumns')]
    public function testColumnSchema(array $columns): void
    {
        parent::testColumnSchema($columns);
    }

    /**
     * MSSQL `findUniqueIndexes()` only returns UNIQUE CONSTRAINTS (`sys.key_constraints` type `'UQ'`), not UNIQUE
     * INDEXES created via `CREATE UNIQUE INDEX`. This is a known limitation.
     *
     * @see \yii\db\mssql\Schema::findUniqueIndexes()
     */
    public function testFindUniqueIndexes(): void
    {
        $db = $this->getConnection(false, true);

        if ($db->getSchema()->getTableSchema('testUniqueConstraint') !== null) {
            $db->createCommand()->dropTable('testUniqueConstraint')->execute();
        }

        $db->createCommand()->setSql(
            <<<SQL
            CREATE TABLE [testUniqueConstraint] (
                [id] INT IDENTITY PRIMARY KEY,
                [col1] VARCHAR(50),
                [col2] VARCHAR(50),
                CONSTRAINT [UQ_col1] UNIQUE ([col1])
            )
            SQL,
        )->execute();

        $db->getSchema()->refreshTableSchema('testUniqueConstraint');
        $tableSchema = $db->getSchema()->getTableSchema('testUniqueConstraint');
        $uniqueIndexes = $db->getSchema()->findUniqueIndexes($tableSchema);

        self::assertSame(
            ['UQ_col1' => ['col1']],
            $uniqueIndexes,
            'MSSQL should find UNIQUE CONSTRAINTS.',
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

    public function testGetStringFieldsSize(): void
    {
        $db = $this->getConnection(false, true);

        $columns = $db->schema->getTableSchema('type', false)->columns;

        foreach ($columns as $name => $column) {
            $type = $column->type;
            $size = $column->size;
            $dbType = $column->dbType;

            if (strpos($name, 'char_') === 0) {
                switch ($name) {
                    case 'char_col':
                        $expectedType = 'char';
                        $expectedSize = 100;
                        $expectedDbType = 'char(100)';
                        break;
                    case 'char_col2':
                        $expectedType = 'string';
                        $expectedSize = 100;
                        $expectedDbType = 'varchar(100)';
                        break;
                    case 'char_col3':
                        $expectedType = 'text';
                        $expectedSize = null;
                        $expectedDbType = 'text';
                        break;
                }

                self::assertSame(
                    $expectedType,
                    $type,
                    "'type' of column '$name' does not match.",
                );
                self::assertSame(
                    $expectedSize,
                    $size,
                    "'size' of column '$name' does not match.",
                );
                self::assertSame(
                    $expectedDbType,
                    $dbType,
                    "'dbType' of column '$name' does not match.",
                );
            }
        }
    }

    #[DataProviderExternal(SchemaProvider::class, 'quoteTableName')]
    public function testQuoteTableName(string $name, string $expectedName): void
    {
        $db = $this->getConnection(false, true);

        $quotedName = $db->schema->quoteTableName($name);

        self::assertSame(
            $expectedName,
            $quotedName,
            "Quoting table name '$name' does not match expected.",
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'getTableSchema')]
    public function testGetTableSchema(string $name, string $expectedName): void
    {
        $db = $this->getConnection(false, true);

        $tableSchema = $db->schema->getTableSchema($name);

        self::assertSame(
            $expectedName,
            $tableSchema->name,
            "Table schema name for '$name' does not match expected.",
        );
    }

    public function testFindColumnsWithCatalogName(): void
    {
        $db = $this->getConnection(false);

        $dbName = $db->createCommand(<<<SQL
            SELECT DB_NAME()
            SQL,
        )->queryScalar();
        $tableSchema = $db->schema->getTableSchema("{$dbName}.dbo.profile");

        self::assertNotNull(
            $tableSchema,
            'Table schema with catalog prefix should be resolved.',
        );
        self::assertSame(
            'profile',
            $tableSchema->name,
            'Table name should be resolved without catalog prefix.',
        );
        self::assertArrayHasKey(
            'id',
            $tableSchema->columns,
            'Column "id" should exist in the resolved table schema.',
        );
    }

    public function testFindColumnsReturnsNullForNonExistentTable(): void
    {
        $db = $this->getConnection(false);

        $tableSchema = $db->schema->getTableSchema('non_existent_table_xyz');

        self::assertNull(
            $tableSchema,
            'Non-existent table should return `null`.',
        );
    }

    public function testCompositePrimaryKeyColumnOrder(): void
    {
        $db = $this->getConnection(false);

        if ($db->getSchema()->getTableSchema('test_composite_pk') !== null) {
            $db->createCommand()->dropTable('test_composite_pk')->execute();
        }

        $db->createCommand()->setSql(
            <<<SQL
            CREATE TABLE [test_composite_pk] (
                [col_b] INT NOT NULL,
                [col_a] INT NOT NULL,
                [col_c] INT NOT NULL,
                [data] VARCHAR(50),
                CONSTRAINT [PK_test_composite] PRIMARY KEY ([col_b], [col_a], [col_c])
            )
            SQL,
        )->execute();

        $db->getSchema()->refreshTableSchema('test_composite_pk');
        $tableSchema = $db->getSchema()->getTableSchema('test_composite_pk');

        self::assertSame(
            [
                'col_b',
                'col_a',
                'col_c',
            ],
            $tableSchema->primaryKey,
            "Composite PK columns should follow 'key_ordinal' order, not alphabetical.",
        );
    }

    public function testCompositeUniqueConstraintColumnOrder(): void
    {
        $db = $this->getConnection(false);

        if ($db->getSchema()->getTableSchema('test_composite_uq') !== null) {
            $db->createCommand()->dropTable('test_composite_uq')->execute();
        }

        $db->createCommand()->setSql(
            <<<SQL
            CREATE TABLE [test_composite_uq] (
                [id] INT IDENTITY PRIMARY KEY,
                [col_z] INT NOT NULL,
                [col_y] INT NOT NULL,
                [col_x] INT NOT NULL,
                CONSTRAINT [UQ_test_composite] UNIQUE ([col_z], [col_y], [col_x])
            )
            SQL,
        )->execute();

        $db->getSchema()->refreshTableSchema('test_composite_uq');
        $tableSchema = $db->getSchema()->getTableSchema('test_composite_uq');
        $uniqueIndexes = $db->getSchema()->findUniqueIndexes($tableSchema);

        self::assertArrayHasKey(
            'UQ_test_composite',
            $uniqueIndexes,
            'Unique constraint should be found by name.',
        );
        self::assertSame(
            ['col_z', 'col_y', 'col_x'],
            $uniqueIndexes['UQ_test_composite'],
            "Composite UQ columns should follow 'key_ordinal' order, not alphabetical.",
        );
    }

    public function testGetPrimaryKey(): void
    {
        $db = $this->getConnection();

        if ($db->getSchema()->getTableSchema('testPKTable') !== null) {
            $db->createCommand()->dropTable('testPKTable')->execute();
        }

        $db->createCommand()->createTable(
            'testPKTable',
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER],
        )->execute();

        $insertResult = $db->getSchema()->insert('testPKTable', ['bar' => 1]);
        $selectResult = $db->createCommand('select [id] from [testPKTable] where [bar]=1')->queryOne();

        self::assertSame(
            $selectResult['id'],
            $insertResult['id'],
            'Inserted primary key should match selected.',
        );
    }
}
