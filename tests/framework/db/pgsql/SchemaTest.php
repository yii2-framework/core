<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\pgsql;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yiiunit\base\db\BaseSchema;
use yiiunit\data\ar\ActiveRecord;
use yiiunit\data\ar\EnumTypeInCustomSchema;
use yiiunit\data\ar\Type;
use yiiunit\framework\db\pgsql\providers\SchemaProvider;

/**
 * Unit test for {@see \yii\db\pgsql\Schema} with PostgreSQL driver.
 *
 * {@see SchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('pgsql')]
#[Group('schema')]
final class SchemaTest extends BaseSchema
{
    public $driverName = 'pgsql';

    protected $expectedSchemas = [
        'public',
    ];

    #[DataProviderExternal(SchemaProvider::class, 'expectedColumns')]
    public function testColumnSchema(array $columns): void
    {
        parent::testColumnSchema($columns);
    }

    public function testCompositeFk(): void
    {
        $schema = $this->getConnection(false, true)->schema;

        $table = $schema->getTableSchema('composite_fk');

        self::assertCount(
            1,
            $table->foreignKeys,
            'Composite FK table should have exactly one foreign key.',
        );
        self::assertTrue(
            isset($table->foreignKeys['fk_composite_fk_order_item']),
            "Foreign key 'fk_composite_fk_order_item' should exist.",
        );
        self::assertEquals(
            'order_item',
            $table->foreignKeys['fk_composite_fk_order_item'][0],
            "Foreign key should reference the 'order_item' table.",
        );
        self::assertEquals(
            'order_id',
            $table->foreignKeys['fk_composite_fk_order_item']['order_id'],
            "Foreign key column 'order_id' should map correctly.",
        );
        self::assertEquals(
            'item_id',
            $table->foreignKeys['fk_composite_fk_order_item']['item_id'],
            "Foreign key column 'item_id' should map correctly.",
        );
    }

    public function testBooleanDefaultValues(): void
    {
        $schema = $this->getConnection(false, true)->schema;

        $table = $schema->getTableSchema('bool_values');

        self::assertTrue(
            $table->getColumn('default_true')->defaultValue,
            "'default_true' should be 'true'.",
        );
        self::assertFalse(
            $table->getColumn('default_false')->defaultValue,
            "'default_false' should be 'false'.",
        );
    }

    public function testSequenceName(): void
    {
        $db = $this->getConnection(false, true);

        $sequenceName = $db->schema->getTableSchema('item')->sequenceName;

        $db->createCommand(
            <<<SQL
            ALTER TABLE "item" ALTER COLUMN "id" SET DEFAULT nextval('item_id_seq_2')
            SQL
        )->execute();
        $db->schema->refreshTableSchema('item');

        self::assertSame(
            'item_id_seq_2',
            $db->schema->getTableSchema('item')->sequenceName,
            'Sequence name should be updated.',
        );

        $db->createCommand(
            <<<SQL
            ALTER TABLE "item" ALTER COLUMN "id" SET DEFAULT nextval('$sequenceName')
            SQL
        )->execute();

        $db->schema->refreshTableSchema('item');

        self::assertSame(
            $sequenceName,
            $db->schema->getTableSchema('item')->sequenceName,
            'Sequence name should be restored.',
        );
    }

    public function testGeneratedValues(): void
    {
        $config = $this->database;

        unset($config['fixture']);

        $db = $this->prepareDatabase($config, realpath(__DIR__ . '/../../../data') . '/postgres12.sql');

        $table = $db->schema->getTableSchema('generated');

        self::assertTrue(
            $table->getColumn('id_always')->autoIncrement,
            "'id_always' should be auto increment.",
        );
        self::assertTrue(
            $table->getColumn('id_primary')->autoIncrement,
            "'id_primary' should be auto increment.",
        );
        self::assertTrue(
            $table->getColumn('id_primary')->isPrimaryKey,
            "'id_primary' should be primary key.",
        );
        self::assertTrue(
            $table->getColumn('id_default')->autoIncrement,
            "'id_default' should be auto increment.",
        );
    }

    public function testFindSchemaNames(): void
    {
        $db = $this->getConnection(false, true);

        self::assertCount(
            3,
            $db->schema->getSchemaNames(),
            "Schema names count should be '3'.",
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'bigintValue')]
    public function testBigintValue(int|float $bigint): void
    {
        $this->mockApplication();

        ActiveRecord::$db = $this->getConnection(false, true);

        Type::deleteAll();

        $type = new Type();

        $type->setAttributes(
            [
                'bigint_col' => $bigint,
                // whatever just to satisfy NOT NULL columns
                'int_col' => 1,
                'char_col' => 'a',
                'float_col' => 0.1,
                'bool_col' => true,
            ],
            false,
        );
        $type->save(false);

        $actual = Type::find()->one();

        self::assertEquals(
            $bigint,
            $actual->bigint_col,
            'Bigint value does not match.',
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/12483
     */
    public function testParenthesisDefaultValue(): void
    {
        $db = $this->getConnection(false, true);

        if ($db->schema->getTableSchema('test_default_parenthesis') !== null) {
            $db->createCommand()->dropTable('test_default_parenthesis')->execute();
        }

        $db->createCommand()->createTable(
            'test_default_parenthesis',
            [
                'id' => 'pk',
                'user_timezone' => 'numeric(5,2) DEFAULT (0)::numeric NOT NULL',
            ],
        )->execute();
        $db->schema->refreshTableSchema('test_default_parenthesis');
        $tableSchema = $db->schema->getTableSchema('test_default_parenthesis');

        self::assertNotNull(
            $tableSchema,
            "Table schema should not be 'null'.",
        );

        $column = $tableSchema->getColumn('user_timezone');

        self::assertNotNull(
            $column,
            "'column' should not be 'null'.",
        );
        self::assertFalse(
            $column->allowNull,
            "'allowNull' should be 'false'.",
        );
        self::assertSame(
            'numeric',
            $column->dbType,
            "'dbType' should be 'numeric'.",
        );
        self::assertSame(
            '0',
            $column->defaultValue,
            "'defaultValue' should be '0'.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/14192
     */
    public function testTimestampNullDefaultValue(): void
    {
        $db = $this->getConnection(false);

        if ($db->schema->getTableSchema('test_timestamp_default_null') !== null) {
            $db->createCommand()->dropTable('test_timestamp_default_null')->execute();
        }

        $db->createCommand()->createTable(
            'test_timestamp_default_null',
            [
                'id' => 'pk',
                'timestamp' => 'timestamp DEFAULT NULL',
            ],
        )->execute();
        $db->schema->refreshTableSchema('test_timestamp_default_null');
        $tableSchema = $db->schema->getTableSchema('test_timestamp_default_null');

        self::assertNull(
            $tableSchema->getColumn('timestamp')->defaultValue,
            "Timestamp 'defaultValue' should be 'null'.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/20329
     */
    public function testTimestampUtcNowDefaultValue(): void
    {
        $db = $this->getConnection(false);

        if ($db->schema->getTableSchema('test_timestamp_utc_now_default') !== null) {
            $db->createCommand()->dropTable('test_timestamp_utc_now_default')->execute();
        }

        $db->createCommand()->createTable('test_timestamp_utc_now_default', [
            'id' => 'pk',
            'timestamp' => 'timestamp DEFAULT timezone(\'UTC\'::text, now()) NOT NULL',
        ])->execute();

        $db->schema->refreshTableSchema('test_timestamp_utc_now_default');
        $tableSchema = $db->schema->getTableSchema('test_timestamp_utc_now_default');

        self::assertEquals(
            new Expression('timezone(\'UTC\'::text, now())'),
            $tableSchema->getColumn('timestamp')->defaultValue,
            "Timestamp 'defaultValue' does not match.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/20329
     */
    public function testTimestampNowDefaultValue(): void
    {
        $db = $this->getConnection(false);

        if ($db->schema->getTableSchema('test_timestamp_now_default') !== null) {
            $db->createCommand()->dropTable('test_timestamp_now_default')->execute();
        }

        $db->createCommand()->createTable(
            'test_timestamp_now_default',
            [
                'id' => 'pk',
                'timestamp' => 'timestamp DEFAULT now()',
            ],
        )->execute();
        $db->schema->refreshTableSchema('test_timestamp_now_default');
        $tableSchema = $db->schema->getTableSchema('test_timestamp_now_default');

        self::assertEquals(
            new Expression('now()'),
            $tableSchema->getColumn('timestamp')->defaultValue,
            "Timestamp 'defaultValue' does not match.",
        );
    }

    /**
     * @see https://github.com/yiisoft/yii2/issues/20329
     */
    public function testTimestampUtcStringDefaultValue(): void
    {
        $db = $this->getConnection(false);

        if ($db->schema->getTableSchema('test_timestamp_utc_string_default') !== null) {
            $db->createCommand()->dropTable('test_timestamp_utc_string_default')->execute();
        }

        $db->createCommand()->createTable(
            'test_timestamp_utc_string_default',
            [
                'id' => 'pk',
                'timestamp' => 'timestamp DEFAULT timezone(\'UTC\'::text, \'1970-01-01 00:00:00+00\'::timestamp with time zone) NOT NULL',
            ],
        )->execute();

        $db->schema->refreshTableSchema('test_timestamp_utc_string_default');
        $tableSchema = $db->schema->getTableSchema('test_timestamp_utc_string_default');

        self::assertEquals(
            new Expression('timezone(\'UTC\'::text, \'1970-01-01 00:00:00+00\'::timestamp with time zone)'),
            $tableSchema->getColumn('timestamp')->defaultValue,
            "Timestamp 'defaultValue' does not match.",
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

    public function testCustomTypeInNonDefaultSchema(): void
    {
        $db = $this->getConnection(false, true);

        ActiveRecord::$db = $db;

        $model = EnumTypeInCustomSchema::find()->one();

        self::assertSame(
            ['VAL2'],
            $model->test_type->getValue(),
            "Custom type value should be 'VAL2'.",
        );

        $model->test_type = ['VAL1'];

        $model->save();

        $modelAfterUpdate = EnumTypeInCustomSchema::find()->one();

        self::assertSame(
            ['VAL1'],
            $modelAfterUpdate->test_type->getValue(),
            "Custom type value should be 'VAL1' after update.",
        );
    }

    public function testThrowNotSupportedExceptionWhenTableSchemaConstraintsDefaultValues(): void
    {
        $db = $this->getConnection(false, true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('PostgreSQL does not support default value constraints.');

        $db->schema->getTableDefaultValues('T_constraints_1');
    }
}
