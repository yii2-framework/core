<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\sqlite\providers;

use yii\db\CheckConstraint;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yiiunit\framework\db\AnyValue;

/**
 * Data provider for {@see \yiiunit\framework\db\sqlite\SchemaTest} test cases.
 *
 * Provides SQLite-specific input/output pairs for schema constraints.
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

        unset($columns['enum_col'], $columns['bit_col'], $columns['json_col']);

        $columns['bool_col']['phpType'] = 'boolean';
        $columns['bool_col']['type'] = 'boolean';
        $columns['bool_col2']['defaultValue'] = true;
        $columns['bool_col2']['phpType'] = 'boolean';
        $columns['bool_col2']['type'] = 'boolean';
        $columns['int_col']['dbType'] = 'integer';
        $columns['int_col']['precision'] = null;
        $columns['int_col']['size'] = null;
        $columns['int_col2']['dbType'] = 'integer';
        $columns['int_col2']['precision'] = null;
        $columns['int_col2']['size'] = null;

        return $result;
    }

    /**
     * @phpstan-return array<
     *   string,
     *   array{
     *     string,
     *     string,
     *     Constraint|Constraint[]|CheckConstraint[]|ForeignKeyConstraint[]|IndexConstraint[]|null,
     *   },
     * >
     */
    public static function constraints(): array
    {
        $result = parent::constraints();

        $result['1: primary key'][2]->name = null;
        $result['1: check'][2][0]->columnNames = null;
        $result['1: check'][2][0]->expression = '"C_check" <> \'\'';
        $result['1: unique'][2][0]->name = AnyValue::getInstance();
        $result['1: index'][2][1]->name = AnyValue::getInstance();
        $result['2: primary key'][2]->name = null;
        $result['2: unique'][2][0]->name = AnyValue::getInstance();
        $result['2: index'][2][2]->name = AnyValue::getInstance();
        $result['3: foreign key'][2][0]->name = null;
        $result['3: index'][2] = [];
        $result['4: primary key'][2]->name = null;
        $result['4: unique'][2][0]->name = AnyValue::getInstance();
        $result['5: primary key'] = [
            'T_upsert',
            'primaryKey',
            new Constraint(
                [
                    'name' => AnyValue::getInstance(),
                    'columnNames' => ['id'],
                ],
            ),
        ];

        return $result;
    }

    /**
     * @phpstan-return array<int, array{string, string}>
     */
    public static function quoteTableName(): array
    {
        return [
            ['`test`', '`test`'],
            ['`test`.`test`', '`test`.`test`'],
            ['test', '`test`'],
            ['test.`test`.test', '`test`.`test`.`test`'],
            ['test.test', '`test`.`test`'],
            ['test.test.test', '`test`.`test`.`test`'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleTableName(): array
    {
        return [
            ...parent::unquoteSimpleTableName(),
            'embedded backtick' => ['`a``b`', 'a`b'],
            'multiple embedded backticks' => ['`a``b``c`', 'a`b`c'],
            'quoted' => ['`myTable`', 'myTable'],
        ];
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleColumnName(): array
    {
        return [
            ...parent::unquoteSimpleColumnName(),
            'embedded backtick' => ['`a``b`', 'a`b'],
            'multiple embedded backticks' => ['`a``b``c`', 'a`b`c'],
            'quoted' => ['`myColumn`', 'myColumn'],
        ];
    }
}
