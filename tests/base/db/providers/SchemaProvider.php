<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\base\db\providers;

use PDO;
use yii\db\CheckConstraint;
use yii\db\Expression;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yiiunit\framework\db\AnyValue;

/**
 * Base data provider for {@see \yiiunit\base\db\BaseSchema} test cases.
 *
 * Provides representative input/output pairs for schema metadata. Driver-specific providers extend this class to add or
 * override test cases.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
class SchemaProvider
{
    /**
     * @phpstan-return array<int, array{array<int, bool>}>
     */
    public static function pdoAttributes(): array
    {
        return [
            [[PDO::ATTR_EMULATE_PREPARES => false]],
            [[PDO::ATTR_EMULATE_PREPARES => true]],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string, string, string}>
     */
    public static function tableSchemaCachePrefixes(): array
    {
        $configs = [
            [
                'prefix' => '',
                'name' => 'type',
            ],
            [
                'prefix' => '',
                'name' => '{{%type}}',
            ],
            [
                'prefix' => 'ty',
                'name' => '{{%pe}}',
            ],
        ];

        $data = [];

        foreach ($configs as $config) {
            foreach ($configs as $testConfig) {
                if ($config === $testConfig) {
                    continue;
                }

                $description = sprintf(
                    "%s (with '%s' prefix) against %s (with '%s' prefix)",
                    $config['name'],
                    $config['prefix'],
                    $testConfig['name'],
                    $testConfig['prefix'],
                );

                $data[$description] = [
                    $config['prefix'],
                    $config['name'],
                    $testConfig['prefix'],
                    $testConfig['name'],
                ];
            }
        }

        return $data;
    }

    /**
     * @phpstan-return array<int, array{int|string, bool}>
     */
    public static function columnSchemaDbTypecastBooleanPhpType(): array
    {
        return [
            [1, true],
            [0, false],
            ['1', true],
            ['0', false],

            // https://github.com/yiisoft/yii2/issues/9006
            ["\1", true],
            ["\0", false],

            // https://github.com/yiisoft/yii2/pull/20122
            ['TRUE', true],
            ['FALSE', false],
            ['true', true],
            ['false', false],
            ['True', true],
            ['False', false],
        ];
    }

    /**
     * @phpstan-return array<int, array{array<string, array<string, mixed>>}>
     */
    public static function expectedColumns(): array
    {
        return [
            [
                [
                    'bit_col' => [
                        'type' => 'integer',
                        'dbType' => 'bit(8)',
                        'phpType' => 'integer',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 8,
                        'precision' => 8,
                        'scale' => null,
                        'defaultValue' => 130, // b'10000010'
                    ],
                    'blob_col' => [
                        'type' => 'binary',
                        'dbType' => 'blob',
                        'phpType' => 'resource',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint(1)',
                        'phpType' => 'integer',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'precision' => 1,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'bool_col2' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint(1)',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'precision' => 1,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'char_col' => [
                        'type' => 'char',
                        'dbType' => 'char(100)',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => 100,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'char_col2' => [
                        'type' => 'string',
                        'dbType' => 'varchar(100)',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 100,
                        'precision' => 100,
                        'scale' => null,
                        'defaultValue' => 'something',
                    ],
                    'char_col3' => [
                        'type' => 'text',
                        'dbType' => 'text',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'enum_col' => [
                        'type' => 'string',
                        'dbType' => "enum('a','B','c,D')",
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => ['a', 'B', 'c,D'],
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'float_col' => [
                        'type' => 'double',
                        'dbType' => 'double(4,3)',
                        'phpType' => 'double',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 4,
                        'precision' => 4,
                        'scale' => 3,
                        'defaultValue' => null,
                    ],
                    'float_col2' => [
                        'type' => 'double',
                        'dbType' => 'double',
                        'phpType' => 'double',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => 1.23,
                    ],
                    'int_col' => [
                        'type' => 'integer',
                        'dbType' => 'int(11)',
                        'phpType' => 'integer',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'precision' => 11,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'int_col2' => [
                        'type' => 'integer',
                        'dbType' => 'int(11)',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 11,
                        'precision' => 11,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'json_col' => [
                        'type' => 'json',
                        'dbType' => 'json',
                        'phpType' => 'array',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => null,
                    ],
                    'numeric_col' => [
                        'type' => 'decimal',
                        'dbType' => 'decimal(5,2)',
                        'phpType' => 'string',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 5,
                        'precision' => 5,
                        'scale' => 2,
                        'defaultValue' => '33.22',
                    ],
                    'smallint_col' => [
                        'type' => 'smallint',
                        'dbType' => 'smallint(1)',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 1,
                        'precision' => 1,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'tinyint_col' => [
                        'type' => 'tinyint',
                        'dbType' => 'tinyint(3)',
                        'phpType' => 'integer',
                        'allowNull' => true,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => 3,
                        'precision' => 3,
                        'scale' => null,
                        'defaultValue' => 1,
                    ],
                    'time' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => '2002-01-01 00:00:00',
                    ],
                    'ts_default' => [
                        'type' => 'timestamp',
                        'dbType' => 'timestamp',
                        'phpType' => 'string',
                        'allowNull' => false,
                        'autoIncrement' => false,
                        'enumValues' => null,
                        'size' => null,
                        'precision' => null,
                        'scale' => null,
                        'defaultValue' => new Expression('CURRENT_TIMESTAMP'),
                    ],
                ],
            ],
        ];
    }

    /**
     * @phpstan-return array<string, array{mixed, int}>
     */
    public static function pdoType(): array
    {
        return [
            'bool false' => [false, PDO::PARAM_BOOL],
            'bool true' => [true, PDO::PARAM_BOOL],
            'empty string' => ['', PDO::PARAM_STR],
            'int 0' => [0, PDO::PARAM_INT],
            'int 1' => [1, PDO::PARAM_INT],
            'int 1337' => [1337, PDO::PARAM_INT],
            'null' => [null, PDO::PARAM_NULL],
            'string' => ['hello', PDO::PARAM_STR],
        ];
    }

    /**
     * @phpstan-return array<
     *   string,
     *   array{string, string, Constraint|Constraint[]|CheckConstraint[]|ForeignKeyConstraint[]|IndexConstraint[]|null},
     * >
     */
    public static function constraints(): array
    {
        return [
            '1: primary key' => [
                'T_constraints_1',
                'primaryKey',
                new Constraint([
                    'name' => AnyValue::getInstance(),
                    'columnNames' => ['C_id'],
                ]),
            ],
            '1: check' => [
                'T_constraints_1',
                'checks',
                [
                    new CheckConstraint([
                        'name' => AnyValue::getInstance(),
                        'columnNames' => ['C_check'],
                        'expression' => "C_check <> ''",
                    ]),
                ],
            ],
            '1: unique' => [
                'T_constraints_1',
                'uniques',
                [
                    new Constraint([
                        'name' => 'CN_unique',
                        'columnNames' => ['C_unique'],
                    ]),
                ],
            ],
            '1: index' => [
                'T_constraints_1',
                'indexes',
                [
                    new IndexConstraint([
                        'name' => AnyValue::getInstance(),
                        'columnNames' => ['C_id'],
                        'isUnique' => true,
                        'isPrimary' => true,
                    ]),
                    new IndexConstraint([
                        'name' => 'CN_unique',
                        'columnNames' => ['C_unique'],
                        'isPrimary' => false,
                        'isUnique' => true,
                    ]),
                ],
            ],
            '2: primary key' => [
                'T_constraints_2',
                'primaryKey',
                new Constraint([
                    'name' => 'CN_pk',
                    'columnNames' => ['C_id_1', 'C_id_2'],
                ]),
            ],
            '2: unique' => [
                'T_constraints_2',
                'uniques',
                [
                    new Constraint([
                        'name' => 'CN_constraints_2_multi',
                        'columnNames' => ['C_index_2_1', 'C_index_2_2'],
                    ]),
                ],
            ],
            '2: index' => [
                'T_constraints_2',
                'indexes',
                [
                    new IndexConstraint([
                        'name' => AnyValue::getInstance(),
                        'columnNames' => ['C_id_1', 'C_id_2'],
                        'isUnique' => true,
                        'isPrimary' => true,
                    ]),
                    new IndexConstraint([
                        'name' => 'CN_constraints_2_single',
                        'columnNames' => ['C_index_1'],
                        'isPrimary' => false,
                        'isUnique' => false,
                    ]),
                    new IndexConstraint([
                        'name' => 'CN_constraints_2_multi',
                        'columnNames' => ['C_index_2_1', 'C_index_2_2'],
                        'isPrimary' => false,
                        'isUnique' => true,
                    ]),
                ],
            ],
            '2: check' => ['T_constraints_2', 'checks', []],

            '3: primary key' => ['T_constraints_3', 'primaryKey', null],
            '3: foreign key' => [
                'T_constraints_3',
                'foreignKeys',
                [
                    new ForeignKeyConstraint([
                        'name' => 'CN_constraints_3',
                        'columnNames' => ['C_fk_id_1', 'C_fk_id_2'],
                        'foreignTableName' => 'T_constraints_2',
                        'foreignColumnNames' => ['C_id_1', 'C_id_2'],
                        'onDelete' => 'CASCADE',
                        'onUpdate' => 'CASCADE',
                    ]),
                ],
            ],
            '3: unique' => ['T_constraints_3', 'uniques', []],
            '3: index' => [
                'T_constraints_3',
                'indexes',
                [
                    new IndexConstraint([
                        'name' => 'CN_constraints_3',
                        'columnNames' => ['C_fk_id_1', 'C_fk_id_2'],
                        'isUnique' => false,
                        'isPrimary' => false,
                    ]),
                ],
            ],
            '3: check' => ['T_constraints_3', 'checks', []],

            '4: primary key' => [
                'T_constraints_4',
                'primaryKey',
                new Constraint([
                    'name' => AnyValue::getInstance(),
                    'columnNames' => ['C_id'],
                ]),
            ],
            '4: unique' => [
                'T_constraints_4',
                'uniques',
                [
                    new Constraint([
                        'name' => 'CN_constraints_4',
                        'columnNames' => ['C_col_1', 'C_col_2'],
                    ]),
                ],
            ],
            '4: check' => ['T_constraints_4', 'checks', []],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleTableName(): array
    {
        return [
            'unquoted' => ['myTable', 'myTable'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleColumnName(): array
    {
        return [
            'unquoted' => ['myColumn', 'myColumn'],
            'asterisk' => ['*', '*'],
        ];
    }
}
