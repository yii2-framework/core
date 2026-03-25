<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\oci;

use Exception;
use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yiiunit\base\db\BaseSchema;
use yiiunit\framework\db\oci\providers\SchemaProvider;

/**
 * Unit test for {@see \yii\db\oci\Schema} with Oracle driver.
 *
 * {@see SchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('oci')]
#[Group('schema')]
final class SchemaTest extends BaseSchema
{
    public $driverName = 'oci';

    protected $expectedSchemas = [];

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

    /**
     * Autoincrement columns detection should be disabled for Oracle
     * because there is no way of associating a column with a sequence.
     */
    public function testAutoincrementDisabled(): void
    {
        $db = $this->getConnection(false, true);

        $table = $db->schema->getTableSchema('order', true);

        self::assertFalse(
            $table->columns['id']->autoIncrement,
            "'autoIncrement' should be disabled for Oracle.",
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
            isset($table->foreignKeys['FK_COMPOSITE_FK_ORDER_ITEM']),
            "Foreign key 'FK_COMPOSITE_FK_ORDER_ITEM' should exist.",
        );
        self::assertSame(
            'order_item',
            $table->foreignKeys['FK_COMPOSITE_FK_ORDER_ITEM'][0],
            "Foreign key should reference the 'order_item' table.",
        );
        self::assertSame(
            'order_id',
            $table->foreignKeys['FK_COMPOSITE_FK_ORDER_ITEM']['order_id'],
            "Foreign key column 'order_id' should map correctly.",
        );
        self::assertSame(
            'item_id',
            $table->foreignKeys['FK_COMPOSITE_FK_ORDER_ITEM']['item_id'],
            "Foreign key column 'item_id' should map correctly.",
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
                'someCol3' => 'string',
            ],
        )->execute();

        $uniqueIndexes = $db->schema->findUniqueIndexes($db->schema->getTableSchema('uniqueIndex', true));

        self::assertEmpty(
            $uniqueIndexes,
            'New table should have no unique indexes.',
        );

        $db->createCommand()->createIndex(
            'somecolUnique',
            'uniqueIndex',
            'somecol',
            true,
        )->execute();

        $uniqueIndexes = $db->schema->findUniqueIndexes($db->schema->getTableSchema('uniqueIndex', true));

        self::assertEquals(
            ['somecolUnique' => ['somecol']],
            $uniqueIndexes,
            'Table should have one unique index after creation.',
        );

        // create another column with upper case letter that fails postgres
        // see https://github.com/yiisoft/yii2/issues/10613
        $db->createCommand()->createIndex('someCol2Unique', 'uniqueIndex', 'someCol2', true)->execute();
        $uniqueIndexes = $db->schema->findUniqueIndexes($db->schema->getTableSchema('uniqueIndex', true));

        self::assertEquals(
            [
                'somecolUnique' => ['somecol'],
                'someCol2Unique' => ['someCol2'],
            ],
            $uniqueIndexes,
            'Table should have two unique indexes.',
        );

        // see https://github.com/yiisoft/yii2/issues/13814
        $db->createCommand()->createIndex('another unique index', 'uniqueIndex', 'someCol3', true)->execute();
        $uniqueIndexes = $db->schema->findUniqueIndexes($db->schema->getTableSchema('uniqueIndex', true));

        self::assertEquals(
            [
                'somecolUnique' => ['somecol'],
                'someCol2Unique' => ['someCol2'],
                'another unique index' => ['someCol3'],
            ],
            $uniqueIndexes,
            'Table should have three unique indexes.',
        );
    }

    /**
     * Verifies that LOB indexes (internal Oracle indexes for CLOB/BLOB columns) are excluded from
     * {@see \yii\db\oci\Schema::loadTableIndexes()} results, preventing NULL column names and PHP deprecation
     * warnings in {@see \yii\db\oci\Schema::quoteColumnName()}.
     *
     * @see https://github.com/yii2-framework/core/issues/21
     */
    public function testLobIndexesExcluded(): void
    {
        $db = $this->getConnection();

        if ($db->schema->getTableSchema('lob_test') !== null) {
            $db->createCommand()->dropTable('lob_test')->execute();
        }

        $db->createCommand()->setSql(
            <<<SQL
            CREATE TABLE "lob_test" (
                "id" NUMBER(10) NOT NULL,
                "content" CLOB,
                "data" BLOB,
                PRIMARY KEY ("id")
            )
            SQL
        )->execute();
        $indexes = $db->schema->getTableIndexes('lob_test', true);

        self::assertCount(
            1,
            $indexes,
            'Only the PRIMARY KEY index should remain after filtering LOB indexes.',
        );

        $primaryIndexes = array_values(
            array_filter($indexes, static fn ($index): bool => $index->isPrimary),
        );

        self::assertCount(
            1,
            $primaryIndexes,
            'Exactly one PRIMARY KEY index should exist.',
        );
        self::assertSame(
            ['id'],
            $primaryIndexes[0]->columnNames,
            "PRIMARY KEY index should contain only the 'id' column.",
        );

        foreach ($indexes as $index) {
            foreach ($index->columnNames as $columnName) {
                self::assertNotNull(
                    $columnName,
                    "LOB index with 'NULL' column name should be excluded.",
                );
                self::assertIsString(
                    $columnName,
                    'Index column name must be a string.',
                );
            }
        }

        $db->createCommand()->dropTable('lob_test')->execute();
    }

    public function testThrowNotSupportedExceptionWhenTableSchemaConstraintsDefaultValues(): void
    {
        $db = $this->getConnection(false, true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('Oracle does not support default value constraints.');

        $db->schema->getTableDefaultValues('T_constraints_1');
    }
}
