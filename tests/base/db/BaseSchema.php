<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db;

use Exception;
use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Depends;
use yii\caching\ArrayCache;
use yii\caching\FileCache;
use yii\db\ColumnSchema;
use yii\db\Constraint;
use yii\db\Schema;
use yii\db\TableSchema;
use yiiunit\base\db\providers\SchemaProvider;
use yiiunit\framework\db\AnyCaseValue;
use yiiunit\framework\db\AnyValue;

use function count;
use function get_class;
use function gettype;
use function is_array;
use function is_object;
use function strtolower;

/**
 * Base test for {@see \yii\db\Schema} across all database drivers.
 *
 * {@see \yiiunit\base\db\providers\SchemaProvider} for base test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
abstract class BaseSchema extends BaseDatabase
{
    /**
     * @var string[]
     */
    protected $expectedSchemas;

    #[DataProviderExternal(SchemaProvider::class, 'unquoteSimpleTableName')]
    public function testUnquoteSimpleTableName(string $input, string $expected): void
    {
        $db = $this->getConnection(false, true);

        self::assertSame(
            $expected,
            $db->schema->unquoteSimpleTableName($input),
            "Should return '$expected'.",
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'unquoteSimpleColumnName')]
    public function testUnquoteSimpleColumnName(string $input, string $expected): void
    {
        $db = $this->getConnection(false, true);

        self::assertSame(
            $expected,
            $db->schema->unquoteSimpleColumnName($input),
            "Should return '$expected'.",
        );
    }

    public function testGetSchemaNames(): void
    {
        $db = $this->getConnection(false, true);

        $schemas = $db->schema->getSchemaNames();

        self::assertNotEmpty(
            $schemas,
            'Schema names should not be empty.',
        );

        foreach ($this->expectedSchemas as $schema) {
            self::assertContains(
                $schema,
                $schemas,
                "Schema '$schema' should be present.",
            );
        }
    }

    #[DataProviderExternal(SchemaProvider::class, 'pdoAttributes')]
    public function testGetTableNames(array $pdoAttributes): void
    {
        $db = $this->getConnection(false, true);

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES && $db->driverName === 'sqlsrv') {
                continue;
            }

            $db->pdo->setAttribute($name, $value);
        }

        $tables = $db->schema->getTableNames();

        if ($this->driverName === 'sqlsrv') {
            $tables = array_map(static fn($item) => trim($item, '[]'), $tables);
        }

        self::assertContains(
            'customer',
            $tables,
            "Table 'customer' should exist.",
        );
        self::assertContains(
            'category',
            $tables,
            "Table 'category' should exist.",
        );
        self::assertContains(
            'item',
            $tables,
            "Table 'item' should exist.",
        );
        self::assertContains(
            'order',
            $tables,
            "Table 'order' should exist.",
        );
        self::assertContains(
            'order_item',
            $tables,
            "Table 'order_item' should exist.",
        );
        self::assertContains(
            'type',
            $tables,
            "Table 'type' should exist.",
        );
        self::assertContains(
            'animal',
            $tables,
            "Table 'animal' should exist.",
        );
        self::assertContains(
            'animal_view',
            $tables,
            "Table 'animal_view' should exist.",
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'pdoAttributes')]
    public function testGetTableSchemas(array $pdoAttributes): void
    {
        $db = $this->getConnection(false, true);

        foreach ($pdoAttributes as $name => $value) {
            if ($name === PDO::ATTR_EMULATE_PREPARES && $db->driverName === 'sqlsrv') {
                continue;
            }

            $db->pdo->setAttribute($name, $value);
        }

        $tables = $db->schema->getTableSchemas();

        self::assertSame(
            count($db-> schema->getTableNames()),
            count($tables),
            'Table schema count should match table name count.',
        );

        foreach ($tables as $table) {
            self::assertInstanceOf(
                TableSchema::class,
                $table,
                'Each table schema should be a TableSchema instance.',
            );
        }
    }

    public function testGetTableSchemasWithAttrCase(): void
    {
        $db = $this->getConnection(false, true);

        $db->slavePdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        self::assertSame(
            count($db->schema->getTableNames()),
            count($db->schema->getTableSchemas()),
            'Table schema count should match with PDO::CASE_LOWER.',
        );

        $db->slavePdo->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        self::assertSame(
            count($db->schema->getTableNames()),
            count($db->schema->getTableSchemas()),
            'Table schema count should match with PDO::CASE_UPPER.',
        );
    }

    public function testGetNonExistingTableSchema(): void
    {
        $db = $this->getConnection(false, true);

        self::assertNull(
            $db->schema->getTableSchema('nonexisting_table'),
            "Non-existing table schema should return 'null'.",
        );
    }

    public function testSchemaCache(): void
    {
        $db = $this->getConnection(false, true);

        $db->schema->db->enableSchemaCache = true;

        $db->schema->db->schemaCache = new FileCache();

        $noCacheTable = $db->schema->getTableSchema('type', true);
        $cachedTable = $db->schema->getTableSchema('type', false);

        self::assertSame(
            $noCacheTable,
            $cachedTable,
            'Cached table schema should match non-cached.',
        );

        $db->createCommand()->renameTable('type', 'type_test');
        $noCacheTable = $db->schema->getTableSchema('type', true);

        self::assertNotSame(
            $noCacheTable,
            $cachedTable,
            'Table schema should differ after renaming the table.',
        );

        $db->createCommand()->renameTable('type_test', 'type');
    }

    #[Depends('testSchemaCache')]
    public function testRefreshTableSchema(): void
    {
        $db = $this->getConnection(false, true);

        $db->schema->db->enableSchemaCache = true;

        $db->schema->db->schemaCache = new FileCache();

        $noCacheTable = $db->schema->getTableSchema('type', true);
        $db->schema->refreshTableSchema('type');

        $refreshedTable = $db->schema->getTableSchema('type', false);

        self::assertNotSame(
            $noCacheTable,
            $refreshedTable,
            'Refreshed table schema should not be the same instance.',
        );
    }

    #[Depends('testSchemaCache')]
    #[DataProviderExternal(SchemaProvider::class, 'tableSchemaCachePrefixes')]
    public function testTableSchemaCacheWithTablePrefixes(
        string $tablePrefix,
        string $tableName,
        string $testTablePrefix,
        string $testTableName,
    ): void {
        $db = $this->getConnection();

        $db->schema->db->enableSchemaCache = true;
        $db->schema->db->tablePrefix = $tablePrefix;

        $db->schema->db->schemaCache = new ArrayCache();

        $noCacheTable = $db->schema->getTableSchema($tableName, true);

        self::assertInstanceOf(
            TableSchema::class,
            $noCacheTable,
            'Table schema should be a TableSchema instance.',
        );

        // Compare
        $db->schema->db->tablePrefix = $testTablePrefix;

        $testNoCacheTable = $db->schema->getTableSchema($testTableName);

        self::assertSame(
            $noCacheTable,
            $testNoCacheTable,
            'Cached table schema should be the same instance regardless of prefix.',
        );

        $db->schema->db->tablePrefix = $tablePrefix;

        $db->schema->refreshTableSchema($tableName);
        $refreshedTable = $db->schema->getTableSchema($tableName, false);

        self::assertInstanceOf(
            TableSchema::class,
            $refreshedTable,
            'Refreshed table schema should be a TableSchema instance.',
        );
        self::assertNotSame(
            $noCacheTable,
            $refreshedTable,
            'Refreshed table schema should not be the same instance as the original.',
        );

        // Compare
        $db->schema->db->tablePrefix = $testTablePrefix;

        $db->schema->refreshTableSchema($testTableName);
        $testRefreshedTable = $db->schema->getTableSchema($testTableName, false);

        self::assertInstanceOf(
            TableSchema::class,
            $testRefreshedTable,
            'Test refreshed table schema should be a TableSchema instance.',
        );
        self::assertEquals(
            $refreshedTable,
            $testRefreshedTable,
            'Refreshed table schemas should be equal.',
        );
        self::assertNotSame(
            $testNoCacheTable,
            $testRefreshedTable,
            'Refreshed table schema should not be the same instance as the cached one.',
        );
    }

    public function testCompositeFk(): void
    {
        $db = $this->getConnection(false, true);

        $table = $db->schema->getTableSchema('composite_fk');

        self::assertCount(
            1,
            $table->foreignKeys,
            'Composite FK table should have exactly one foreign key.',
        );
        self::assertTrue(
            isset($table->foreignKeys['FK_composite_fk_order_item']),
            "Foreign key 'FK_composite_fk_order_item' should exist.",
        );
        self::assertSame(
            'order_item',
            $table->foreignKeys['FK_composite_fk_order_item'][0],
            "Foreign key should reference the 'order_item' table.",
        );
        self::assertSame(
            'order_id',
            $table->foreignKeys['FK_composite_fk_order_item']['order_id'],
            "Foreign key column 'order_id' should map correctly.",
        );
        self::assertSame(
            'item_id',
            $table->foreignKeys['FK_composite_fk_order_item']['item_id'],
            "Foreign key column 'item_id' should map correctly.",
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'pdoType')]
    public function testGetPDOType(mixed $value, int $expected): void
    {
        $db = $this->getConnection(false, true);

        self::assertSame(
            $expected,
            $db->schema->getPdoType($value),
            'PDO type does not match expected value.',
        );
    }

    public function testGetPDOTypeLob(): void
    {
        $db = $this->getConnection(false, true);

        $fp = fopen(__FILE__, 'rb');

        self::assertSame(
            PDO::PARAM_LOB,
            $db->schema->getPdoType($fp),
            'PDO type for LOB resource does not match.',
        );

        fclose($fp);
    }

    public function testNegativeDefaultValues(): void
    {
        $db = $this->getConnection(false, true);

        $table = $db->schema->getTableSchema('negative_default_values');

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

    #[DataProviderExternal(SchemaProvider::class, 'expectedColumns')]
    public function testColumnSchema(array $columns): void
    {
        $db = $this->getConnection(false, true);

        $table = $db->schema->getTableSchema('type', true);

        $expectedColNames = array_keys($columns);
        sort($expectedColNames);

        $colNames = $table->columnNames;

        sort($colNames);

        self::assertSame(
            $expectedColNames,
            $colNames,
            'Column names do not match expected.',
        );

        foreach ($table->columns as $name => $column) {
            $expected = $columns[$name];

            self::assertSame(
                $expected['dbType'],
                $column->dbType,
                "'dbType' of column '$name' does not match. type is '$column->type', dbType is '$column->dbType'.",
            );
            self::assertSame(
                $expected['phpType'],
                $column->phpType,
                "'phpType' of column '$name' does not match. type is '$column->type', dbType is '$column->dbType'.",
            );
            self::assertSame(
                $expected['type'],
                $column->type,
                "'type' of column '$name' does not match.",
            );
            self::assertSame(
                $expected['allowNull'],
                $column->allowNull,
                "'allowNull' of column '$name' does not match.",
            );
            self::assertSame(
                $expected['autoIncrement'],
                $column->autoIncrement,
                "'autoIncrement' of column '$name' does not match.",
            );
            self::assertSame(
                $expected['enumValues'],
                $column->enumValues,
                "'enumValues' of column '$name' does not match.",
            );
            self::assertSame(
                $expected['size'],
                $column->size,
                "'size' of column '$name' does not match.",
            );
            self::assertSame(
                $expected['precision'],
                $column->precision,
                "'precision' of column '$name' does not match.",
            );
            self::assertSame(
                $expected['scale'],
                $column->scale,
                "'scale' of column '$name' does not match.",
            );

            if (is_object($expected['defaultValue'])) {
                self::assertIsObject(
                    $column->defaultValue,
                    "'defaultValue' of column '$name' is expected to be an object but it is not.",
                );
                self::assertSame(
                    (string) $expected['defaultValue'],
                    (string) $column->defaultValue,
                    "'defaultValue' of column '$name' does not match.",
                );
            } else {
                self::assertSame(
                    $expected['defaultValue'],
                    $column->defaultValue,
                    "'defaultValue' of column '$name' does not match."
                );
            }

            if (isset($expected['dimension'])) { // PgSQL only
                self::assertSame(
                    $expected['dimension'],
                    $column->dimension,
                    "'dimension' of column '$name' does not match.",
                );
            }
        }
    }

    public function testColumnSchemaDbTypecastWithEmptyCharType(): void
    {
        $columnSchema = new ColumnSchema(['type' => Schema::TYPE_CHAR]);

        self::assertEmpty(
            $columnSchema->dbTypecast(''),
            'Empty char type should return empty string.',
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'columnSchemaDbTypecastBooleanPhpType')]
    public function testColumnSchemaDbTypecastBooleanPhpType(mixed $value, bool $expected): void
    {
        $columnSchema = new ColumnSchema(['phpType' => Schema::TYPE_BOOLEAN]);

        self::assertSame(
            $expected,
            $columnSchema->dbTypecast($value),
            'Boolean typecast does not match expected value.',
        );
    }

    public function testFindUniqueIndexes(): void
    {
        $db = $this->getConnection();

        try {
            $db->createCommand()->dropTable('uniqueIndex')->execute();
        } catch (Exception $e) {
        }

        $db->createCommand()->createTable(
            'uniqueIndex',
            [
                'somecol' => 'string',
                'someCol2' => 'string',
            ],
        )->execute();

        $uniqueIndexes = $db->getSchema()->findUniqueIndexes($db->getSchema()->getTableSchema('uniqueIndex', true));

        self::assertEmpty(
            $uniqueIndexes,
            'New table should have no unique indexes.',
        );

        $db->createCommand()->createIndex('somecolUnique', 'uniqueIndex', 'somecol', true)->execute();
        $uniqueIndexes = $db->getSchema()->findUniqueIndexes($db->getSchema()->getTableSchema('uniqueIndex', true));

        self::assertSame(
            ['somecolUnique' => ['somecol']],
            $uniqueIndexes,
            'Table should have one unique index after creation.',
        );

        // create another column with upper case letter that fails postgres
        // see https://github.com/yiisoft/yii2/issues/10613
        $db->createCommand()->createIndex('someCol2Unique', 'uniqueIndex', 'someCol2', true)->execute();
        $uniqueIndexes = $db->getSchema()->findUniqueIndexes($db->getSchema()->getTableSchema('uniqueIndex', true));

        ksort($uniqueIndexes);

        self::assertSame(
            [
                'someCol2Unique' => ['someCol2'],
                'somecolUnique' => ['somecol'],
            ],
            $uniqueIndexes,
            'Table should have two unique indexes.',
        );

        // see https://github.com/yiisoft/yii2/issues/13814
        $db->createCommand()->createIndex('another unique index', 'uniqueIndex', 'someCol2', true)->execute();
        $uniqueIndexes = $db->getSchema()->findUniqueIndexes($db->getSchema()->getTableSchema('uniqueIndex', true));

        ksort($uniqueIndexes);

        self::assertSame(
            [
                'another unique index' => ['someCol2'],
                'someCol2Unique' => ['someCol2'],
                'somecolUnique' => ['somecol'],
            ],
            $uniqueIndexes,
            'Table should have three unique indexes.',
        );
    }

    public function testContraintTablesExistance(): void
    {
        $db = $this->getConnection(false, true);

        $tableNames = [
            'T_constraints_1',
            'T_constraints_2',
            'T_constraints_3',
            'T_constraints_4',
        ];

        foreach ($tableNames as $tableName) {
            $tableSchema = $db->schema->getTableSchema($tableName);

            self::assertInstanceOf(
                TableSchema::class,
                $tableSchema,
                "Table '$tableName' should exist in the schema.",
            );
        }
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

    protected function assertMetadataEquals($expected, $actual)
    {
        switch (strtolower(gettype($expected))) {
            case 'object':
                self::assertIsObject(
                    $actual,
                    "Expected an 'object'.",
                );
                break;
            case 'array':
                self::assertIsArray(
                    $actual,
                    "Expected an 'array'.",
                );
                break;
            case 'null':
                self::assertNull(
                    $actual,
                    "Expected 'null'.",
                );
                break;
        }

        if (is_array($expected)) {
            $this->normalizeArrayKeys($expected, false);
            $this->normalizeArrayKeys($actual, false);
        }

        $this->normalizeConstraints($expected, $actual);

        if (is_array($expected)) {
            $this->normalizeArrayKeys($expected, true);
            $this->normalizeArrayKeys($actual, true);
        }

        self::assertEquals(
            $expected,
            $actual,
            'Metadata does not match expected value.',
        );
    }

    protected function normalizeArrayKeys(array &$array, $caseSensitive)
    {
        $newArray = [];

        foreach ($array as $value) {
            if ($value instanceof Constraint) {
                $key = (array) $value;

                unset($key['name'], $key['foreignSchemaName']);

                foreach ($key as $keyName => $keyValue) {
                    if ($keyValue instanceof AnyCaseValue) {
                        $key[$keyName] = $keyValue->value;
                    } elseif ($keyValue instanceof AnyValue) {
                        $key[$keyName] = '[AnyValue]';
                    }
                }

                ksort($key, SORT_STRING);
                $newArray[$caseSensitive ? json_encode($key) : strtolower(json_encode($key))] = $value;
            } else {
                $newArray[] = $value;
            }
        }

        ksort($newArray, SORT_STRING);

        $array = $newArray;
    }

    protected function normalizeConstraints(&$expected, &$actual)
    {
        if (is_array($expected)) {
            foreach ($expected as $key => $value) {
                if (!$value instanceof Constraint || !isset($actual[$key]) || !$actual[$key] instanceof Constraint) {
                    continue;
                }

                $this->normalizeConstraintPair($value, $actual[$key]);
            }
        } elseif ($expected instanceof Constraint && $actual instanceof Constraint) {
            $this->normalizeConstraintPair($expected, $actual);
        }
    }

    protected function normalizeConstraintPair(Constraint $expectedConstraint, Constraint $actualConstraint)
    {
        if (get_class($expectedConstraint) !== get_class($actualConstraint)) {
            return;
        }

        foreach (array_keys((array) $expectedConstraint) as $name) {
            if ($expectedConstraint->$name instanceof AnyValue) {
                $actualConstraint->$name = $expectedConstraint->$name;
            } elseif ($expectedConstraint->$name instanceof AnyCaseValue) {
                $actualConstraint->$name = new AnyCaseValue($actualConstraint->$name);
            }
        }
    }
}
