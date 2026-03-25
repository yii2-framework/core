<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql;

use PDO;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use PHPUnit\Framework\Attributes\Group;
use yii\base\NotSupportedException;
use yii\db\Expression;
use yii\db\mysql\ColumnSchema;
use yii\db\mysql\Schema;
use yiiunit\base\db\BaseSchema;
use yiiunit\framework\db\mysql\providers\SchemaProvider;

use function stripos;

/**
 * Unit test for {@see \yii\db\mysql\Schema} with MySQL driver.
 *
 * {@see SchemaProvider} for test case data providers.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
#[Group('db')]
#[Group('mysql')]
#[Group('schema')]
final class SchemaTest extends BaseSchema
{
    public $driverName = 'mysql';

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

    public function testLoadDefaultDatetimeColumn(): void
    {
        $db = $this->getConnection(false, true);

        $sql = <<<SQL
        CREATE TABLE  IF NOT EXISTS `datetime_test`  (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `dt` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `ts` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        SQL;

        $db->createCommand($sql)->execute();

        $schema = $db->getTableSchema('datetime_test');

        $dt = $schema->columns['dt'];

        self::assertInstanceOf(
            Expression::class,
            $dt->defaultValue,
            "'defaultValue' should be an Expression instance.",
        );
        self::assertEquals(
            'CURRENT_TIMESTAMP',
            (string) $dt->defaultValue,
            "'defaultValue' should be CURRENT_TIMESTAMP.",
        );
    }

    public function testDefaultDatetimeColumnWithMicrosecs(): void
    {
        $db = $this->getConnection(false, true);

        $sql = <<<SQL
        CREATE TABLE  IF NOT EXISTS `current_timestamp_test`  (
            `dt` datetime(2) NOT NULL DEFAULT CURRENT_TIMESTAMP(2),
            `ts` timestamp(3) NOT NULL DEFAULT CURRENT_TIMESTAMP(3)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8
        SQL;

        $db->createCommand($sql)->execute();

        $schema = $db->getTableSchema('current_timestamp_test');

        $dt = $schema->columns['dt'];

        self::assertInstanceOf(
            Expression::class,
            $dt->defaultValue,
            "'defaultValue' should be an Expression instance.",
        );
        self::assertEquals(
            'CURRENT_TIMESTAMP(2)',
            (string) $dt->defaultValue,
            "'defaultValue' should be 'CURRENT_TIMESTAMP(2)'.",
        );

        $ts = $schema->columns['ts'];

        self::assertInstanceOf(
            Expression::class,
            $ts->defaultValue,
            "'defaultValue' should be an Expression instance.",
        );
        self::assertEquals(
            'CURRENT_TIMESTAMP(3)',
            (string) $ts->defaultValue,
            "'defaultValue' should be 'CURRENT_TIMESTAMP(3)'.",
        );
    }

    public function testGetSchemaNames(): void
    {
        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage(Schema::class . ' does not support fetching all schema names.');

        parent::testGetSchemaNames();
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraints(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->getConnection(false, true);

        $version = $db->getServerVersion();

        if (
            $this->driverName === 'mysql' &&
            stripos($version, 'MariaDb') === false &&
            $tableName === 'T_constraints_1' &&
            $type === 'checks'
        ) {
            $expected[0]->expression = "(`C_check` <> _utf8mb4\\'\\')";
        }

        $constraints = $db->schema->{'getTable' . ucfirst($type)}($tableName);

        self::assertMetadataEquals($expected, $constraints);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoUppercase(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->getConnection(false, true);

        $version = $db->getServerVersion();

        if (
            $this->driverName === 'mysql' &&
            stripos($version, 'MariaDb') === false &&
            $tableName === 'T_constraints_1' &&
            $type === 'checks'
        ) {
            $expected[0]->expression = "(`C_check` <> _utf8mb4\\'\\')";
        }

        $db->getSlavePdo(true)->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $constraints = $db->schema->{'getTable' . ucfirst($type)}($tableName, true);

        self::assertMetadataEquals($expected, $constraints);
    }

    #[DataProviderExternal(SchemaProvider::class, 'constraints')]
    public function testTableSchemaConstraintsWithPdoLowercase(string $tableName, string $type, mixed $expected): void
    {
        $db = $this->getConnection(false, true);

        $version = $db->getServerVersion();

        if (
            $this->driverName === 'mysql' &&
            stripos($version, 'MariaDb') === false &&
            $tableName === 'T_constraints_1' &&
            $type === 'checks'
        ) {
            $expected[0]->expression = "(`C_check` <> _utf8mb4\\'\\')";
        }

        $db->getSlavePdo(true)->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);

        $constraints = $db->schema->{'getTable' . ucfirst($type)}($tableName, true);

        self::assertMetadataEquals($expected, $constraints);
    }

    /**
     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is displayed
     * as CURRENT_TIMESTAMP up until MariaDB 10.2.2, and as current_timestamp() from MariaDB 10.2.3.
     *
     * @see https://mariadb.com/kb/en/library/now/#description
     * @see https://github.com/yiisoft/yii2/issues/15167
     */
    public function testAlternativeDisplayOfDefaultCurrentTimestampInMariaDB(): void
    {
        /**
         * We do not have a real database MariaDB >= 10.2.3 for tests, so we emulate the information that database
         * returns in response to the query `SHOW FULL COLUMNS FROM ...`
         */
        $schema = new Schema();

        $column = $this->invokeMethod(
            $schema,
            'loadColumnSchema',
            [
                [
                    'field' => 'emulated_MariaDB_field',
                    'type' => 'timestamp',
                    'collation' => null,
                    'null' => 'NO',
                    'key' => '',
                    'default' => 'current_timestamp()',
                    'extra' => '',
                    'privileges' => 'select,insert,update,references',
                    'comment' => '',
                ],
            ],
        );

        $column->defaultValue = $column->defaultPhpTypecast($column->defaultValue);

        self::assertInstanceOf(
            ColumnSchema::class,
            $column,
            "'column' should be a ColumnSchema instance.",
        );
        self::assertInstanceOf(
            Expression::class,
            $column->defaultValue,
            "'defaultValue' should be an Expression instance.",
        );
        self::assertEquals(
            'CURRENT_TIMESTAMP',
            $column->defaultValue,
            "'defaultValue' should be 'CURRENT_TIMESTAMP'.",
        );
    }

    /**
     * When displayed in the INFORMATION_SCHEMA.COLUMNS table, a default CURRENT TIMESTAMP is provided
     * as NULL.
     *
     * @see https://github.com/yiisoft/yii2/issues/19047
     */
    public function testAlternativeDisplayOfDefaultCurrentTimestampAsNullInMariaDB(): void
    {
        $schema = new Schema();

        $column = $this->invokeMethod(
            $schema,
            'loadColumnSchema',
            [
                [
                    'field' => 'emulated_MariaDB_field',
                    'type' => 'timestamp',
                    'collation' => null,
                    'null' => 'NO',
                    'key' => '',
                    'default' => null,
                    'extra' => '',
                    'privileges' => 'select,insert,update,references',
                    'comment' => '',
                ],
            ],
        );

        $column->defaultValue = $column->defaultPhpTypecast($column->defaultValue);

        self::assertInstanceOf(
            ColumnSchema::class,
            $column,
            "'column' should be a ColumnSchema instance.",
        );
        self::assertNull(
            $column->defaultValue,
            "'defaultValue' should be 'null'.",
        );
    }

    #[DataProviderExternal(SchemaProvider::class, 'expectedColumns')]
    public function testColumnSchema(array $columns): void
    {
        $db = $this->getConnection(false, true);

        $version = $db->getServerVersion();

        if (stripos($version, 'MariaDb') !== false) {
            $columns['int_col']['dbType'] = 'int(11)';
            $columns['int_col']['size'] = 11;
            $columns['int_col']['precision'] = 11;
            $columns['int_col2']['dbType'] = 'int(11)';
            $columns['int_col2']['size'] = 11;
            $columns['int_col2']['precision'] = 11;
            $columns['int_col3']['dbType'] = 'int(11) unsigned';
            $columns['int_col3']['size'] = 11;
            $columns['int_col3']['precision'] = 11;
            $columns['tinyint_col']['dbType'] = 'tinyint(3)';
            $columns['tinyint_col']['size'] = 3;
            $columns['tinyint_col']['precision'] = 3;
            $columns['smallint_col']['dbType'] = 'smallint(1)';
            $columns['smallint_col']['size'] = 1;
            $columns['smallint_col']['precision'] = 1;
            $columns['bigint_col']['dbType'] = 'bigint(20) unsigned';
            $columns['bigint_col']['size'] = 20;
            $columns['bigint_col']['precision'] = 20;
        }

        parent::testColumnSchema($columns);
    }

    public function testThrowNotSupportedExceptionWhenTableSchemaConstraintsDefaultValues(): void
    {
        $db = $this->getConnection(false, true);

        $this->expectException(NotSupportedException::class);
        $this->expectExceptionMessage('MySQL does not support default value constraints.');

        $db->schema->getTableDefaultValues('T_constraints_1');
    }
}
