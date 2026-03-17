<?php

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql;

use yii\base\NotSupportedException;
use yii\db\DefaultValueConstraint;
use yii\db\mssql\Schema;
use yiiunit\base\db\BaseSchema;
use yiiunit\framework\db\AnyValue;

use function in_array;

/**
 * @group db
 * @group mssql
 */
class SchemaTest extends BaseSchema
{
    public $driverName = 'sqlsrv';

    protected $expectedSchemas = [
        'dbo',
    ];

    public static function constraintsProvider(): array
    {
        $result = parent::constraintsProvider();
        $result['1: check'][2][0]->expression = '([C_check]<>\'\')';
        $result['1: default'][2] = [];
        $result['1: default'][2][] = new DefaultValueConstraint([
            'name' => AnyValue::getInstance(),
            'columnNames' => ['C_default'],
            'value' => '((0))',
        ]);

        $result['2: default'][2] = [];

        $result['3: foreign key'][2][0]->foreignSchemaName = 'dbo';
        $result['3: index'][2] = [];
        $result['3: default'][2] = [];

        $result['4: default'][2] = [];
        return $result;
    }

    public function testGetStringFieldsSize(): void
    {
        /** @var Connection $db */
        $db = $this->getConnection();

        /** @var Schema $schema */
        $schema = $db->schema;

        $columns = $schema->getTableSchema('type', false)->columns;

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

                $this->assertEquals($expectedType, $type);
                $this->assertEquals($expectedSize, $size);
                $this->assertEquals($expectedDbType, $dbType);
            }
        }
    }

    /**
     * @dataProvider quoteTableNameDataProvider
     * @param $name
     * @param $expectedName
     * @throws NotSupportedException
     */
    public function testQuoteTableName($name, $expectedName): void
    {
        $schema = $this->getConnection()->getSchema();
        $quotedName = $schema->quoteTableName($name);
        $this->assertEquals($expectedName, $quotedName);
    }

    public static function quoteTableNameDataProvider(): array
    {
        return [
            ['test', '[test]'],
            ['test.test', '[test].[test]'],
            ['test.test.test', '[test].[test].[test]'],
            ['[test]', '[test]'],
            ['[test].[test]', '[test].[test]'],
            ['test.[test.test]', '[test].[test.test]'],
            ['test.test.[test.test]', '[test].[test].[test.test]'],
            ['[test].[test.test]', '[test].[test.test]'],
        ];
    }

    /**
     * @dataProvider getTableSchemaDataProvider
     * @param $name
     * @param $expectedName
     * @throws NotSupportedException
     */
    public function testGetTableSchema($name, $expectedName): void
    {
        $schema = $this->getConnection()->getSchema();
        $tableSchema = $schema->getTableSchema($name);
        $this->assertEquals($expectedName, $tableSchema->name);
    }

    public static function getTableSchemaDataProvider(): array
    {
        return [
            ['[dbo].[profile]', 'profile'],
            ['dbo.profile', 'profile'],
            ['profile', 'profile'],
            ['dbo.[table.with.special.characters]', 'table.with.special.characters'],
        ];
    }

    public function getExpectedColumns()
    {
        $columns = parent::getExpectedColumns();

        unset($columns['enum_col']);
        unset($columns['ts_default']);
        unset($columns['bit_col']);
        unset($columns['json_col']);

        $columns['int_col']['dbType'] = 'int';
        $columns['int_col2']['dbType'] = 'int';
        $columns['tinyint_col']['dbType'] = 'tinyint';
        $columns['smallint_col']['dbType'] = 'smallint';
        $columns['float_col']['dbType'] = 'decimal(4,3)';
        $columns['float_col']['phpType'] = 'string';
        $columns['float_col']['type'] = 'decimal';
        $columns['float_col2']['dbType'] = 'float';
        $columns['float_col2']['phpType'] = 'double';
        $columns['float_col2']['type'] = 'float';
        $columns['float_col2']['scale'] = null;
        $columns['blob_col']['dbType'] = 'varbinary(max)';
        $columns['time']['dbType'] = 'datetime';
        $columns['time']['type'] = 'datetime';
        $columns['bool_col']['dbType'] = 'tinyint';
        $columns['bool_col2']['dbType'] = 'tinyint';

        array_walk(
            $columns,
            static function (&$item) {
                $item['enumValues'] = [];
            },
        );

        array_walk(
            $columns,
            static function (&$item, $name) {
                if (!in_array($name, ['char_col', 'char_col2', 'float_col', 'numeric_col'])) {
                    $item['size'] = null;
                }
            },
        );

        array_walk(
            $columns,
            static function (&$item, $name) {
                if (!in_array($name, ['char_col', 'char_col2', 'float_col', 'numeric_col'])) {
                    $item['precision'] = null;
                }
            },
        );

        return $columns;
    }

    public function testFindColumnsWithCatalogName(): void
    {
        $db = $this->getConnection(false);
        $dbName = $db->createCommand('SELECT DB_NAME()')->queryScalar();
        $tableSchema = $db->getSchema()->getTableSchema("{$dbName}.dbo.profile");

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

        $tableSchema = $db->getSchema()->getTableSchema('non_existent_table_xyz');

        self::assertNull(
            $tableSchema,
            'Non-existent table should return null.',
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
            ['col_b', 'col_a', 'col_c'],
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
            ['id' => Schema::TYPE_PK, 'bar' => Schema::TYPE_INTEGER]
        )->execute();

        $insertResult = $db->getSchema()->insert('testPKTable', ['bar' => 1]);
        $selectResult = $db->createCommand('select [id] from [testPKTable] where [bar]=1')->queryOne();

        $this->assertEquals($selectResult['id'], $insertResult['id']);
    }
}
