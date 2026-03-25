<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mssql\providers;

use yii\db\CheckConstraint;
use yii\db\Constraint;
use yii\db\DefaultValueConstraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yiiunit\framework\db\AnyValue;

use function in_array;

/**
 * Data provider for {@see \yiiunit\framework\db\mssql\SchemaTest} test cases.
 *
 * Provides MSSQL-specific input/output pairs for schema constraints and default value constraints.
 *
 * @author Wilmer Arambula <terabytesoftw@gmail.com>
 * @since 2.2
 */
final class SchemaProvider extends \yiiunit\base\db\providers\SchemaProvider
{
    /**
     * @phpstan-return array<int, array{array<string, array<string, mixed>>}>
     */
    public static function expectedColumns(): array
    {
        $result = parent::expectedColumns();

        $columns = &$result[0][0];

        unset($columns['enum_col'], $columns['ts_default'], $columns['bit_col'], $columns['json_col']);

        $columns['blob_col']['dbType'] = 'varbinary(max)';
        $columns['bool_col']['dbType'] = 'tinyint';
        $columns['bool_col2']['dbType'] = 'tinyint';
        $columns['float_col']['dbType'] = 'decimal(4,3)';
        $columns['float_col']['phpType'] = 'string';
        $columns['float_col']['type'] = 'decimal';
        $columns['float_col2']['dbType'] = 'float';
        $columns['float_col2']['phpType'] = 'double';
        $columns['float_col2']['scale'] = null;
        $columns['float_col2']['type'] = 'float';
        $columns['int_col']['dbType'] = 'int';
        $columns['int_col2']['dbType'] = 'int';
        $columns['smallint_col']['dbType'] = 'smallint';
        $columns['time']['dbType'] = 'datetime';
        $columns['time']['type'] = 'datetime';
        $columns['tinyint_col']['dbType'] = 'tinyint';

        array_walk(
            $columns,
            static function (&$item, $name) {
                $item['enumValues'] = [];

                if (!in_array($name, ['char_col', 'char_col2', 'float_col', 'numeric_col'])) {
                    $item['size'] = null;
                    $item['precision'] = null;
                }
            },
        );

        return $result;
    }

    /**
     * @phpstan-return array<
     *   string,
     *   array{
     *     string,
     *     string,
     *     Constraint|Constraint[]|CheckConstraint[]|DefaultValueConstraint[]|ForeignKeyConstraint[]|IndexConstraint[]|null,
     *   },
     * >
     */
    public static function constraints(): array
    {
        $result = parent::constraints();

        $result['1: check'][2][0]->expression = '([C_check]<>\'\')';
        $result['1: default'] = [
            'T_constraints_1',
            'defaultValues',
            [
                new DefaultValueConstraint(
                    [
                        'name' => AnyValue::getInstance(),
                        'columnNames' => ['C_default'],
                        'value' => '((0))',
                    ],
                ),
            ],
        ];

        $result['2: default'] = ['T_constraints_2', 'defaultValues', []];
        $result['3: foreign key'][2][0]->foreignSchemaName = 'dbo';
        $result['3: index'][2] = [];
        $result['3: default'] = ['T_constraints_3', 'defaultValues', []];
        $result['4: default'] = ['T_constraints_4', 'defaultValues', []];

        return $result;
    }

    /**
     * @phpstan-return array<int, array{string, string}>
     */
    public static function quoteTableName(): array
    {
        return [
            ['[test]', '[test]'],
            ['[test].[test.test]', '[test].[test.test]'],
            ['[test].[test]', '[test].[test]'],
            ['test', '[test]'],
            ['test.[test.test]', '[test].[test.test]'],
            ['test.test', '[test].[test]'],
            ['test.test.[test.test]', '[test].[test].[test.test]'],
            ['test.test.test', '[test].[test].[test]'],
        ];
    }

    /**
     * @phpstan-return array<int, array{string, string}>
     */
    public static function getTableSchema(): array
    {
        return [
            ['[dbo].[profile]', 'profile'],
            ['dbo.[table.with.special.characters]', 'table.with.special.characters'],
            ['dbo.profile', 'profile'],
            ['profile', 'profile'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleTableName(): array
    {
        return array_merge(parent::unquoteSimpleTableName(), [
            'quoted' => ['[myTable]', 'myTable'],
            'embedded closing bracket' => ['[a]]b]', 'a]b'],
            'multiple embedded closing brackets' => ['[a]]b]]c]', 'a]b]c'],
        ]);
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleColumnName(): array
    {
        return array_merge(parent::unquoteSimpleColumnName(), [
            'quoted' => ['[myColumn]', 'myColumn'],
            'embedded closing bracket' => ['[a]]b]', 'a]b'],
            'multiple embedded closing brackets' => ['[a]]b]]c]', 'a]b]c'],
        ]);
    }
}
