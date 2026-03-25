<?php

declare(strict_types=1);

/**
 * @link https://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license https://www.yiiframework.com/license/
 */

namespace yiiunit\framework\db\mysql\providers;

use yii\db\CheckConstraint;
use yii\db\Constraint;
use yii\db\ForeignKeyConstraint;
use yii\db\IndexConstraint;
use yiiunit\framework\db\AnyCaseValue;

/**
 * Data provider for {@see \yiiunit\framework\db\mysql\SchemaTest} test cases.
 *
 * Provides MySQL-specific input/output pairs for schema constraints.
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

        $result[0][0] = array_merge(
            $result[0][0],
            [
                'bigint_col' => [
                    'type' => 'bigint',
                    'dbType' => 'bigint unsigned',
                    'phpType' => 'string',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => null,
                ],
                'int_col' => [
                    'type' => 'integer',
                    'dbType' => 'int',
                    'phpType' => 'integer',
                    'allowNull' => false,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => null,
                ],
                'int_col2' => [
                    'type' => 'integer',
                    'dbType' => 'int',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'int_col3' => [
                    'type' => 'integer',
                    'dbType' => 'int unsigned',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'smallint_col' => [
                    'type' => 'smallint',
                    'dbType' => 'smallint',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'tinyint_col' => [
                    'type' => 'tinyint',
                    'dbType' => 'tinyint',
                    'phpType' => 'integer',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => null,
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => 1,
                ],
                'enum_col_paren' => [
                    'type' => 'string',
                    'dbType' => "enum('a)','b(','c)d')",
                    'phpType' => 'string',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => ['a)', 'b(', 'c)d'],
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => null,
                ],
                'enum_col_quote' => [
                    'type' => 'string',
                    'dbType' => "enum('it''s','they''re','plain')",
                    'phpType' => 'string',
                    'allowNull' => true,
                    'autoIncrement' => false,
                    'enumValues' => ["it's", "they're", 'plain'],
                    'size' => null,
                    'precision' => null,
                    'scale' => null,
                    'defaultValue' => null,
                ],
            ],
        );

        return $result;
    }

    /**
     * @phpstan-return array<
     *   string,
     *   array{
     *     string,
     *     string,
     *     Constraint|Constraint[]|CheckConstraint[]|ForeignKeyConstraint[]|IndexConstraint[]|null,
     * },
     * >
     */
    public static function constraints(): array
    {
        $result = parent::constraints();

        $result['1: check'][2][0]->columnNames = null;
        $result['1: check'][2][0]->expression = "`C_check` <> ''";
        $result['2: primary key'][2]->name = null;
        $result['3: foreign key'][2][0]->foreignTableName = new AnyCaseValue('T_constraints_2');

        return $result;
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleTableName(): array
    {
        return array_merge(parent::unquoteSimpleTableName(), [
            'quoted' => ['`myTable`', 'myTable'],
            'embedded backtick' => ['`a``b`', 'a`b'],
            'multiple embedded backticks' => ['`a``b``c`', 'a`b`c'],
        ]);
    }

    /**
     * @phpstan-return array<string, array{string, string}>
     */
    public static function unquoteSimpleColumnName(): array
    {
        return array_merge(parent::unquoteSimpleColumnName(), [
            'quoted' => ['`myColumn`', 'myColumn'],
            'embedded backtick' => ['`a``b`', 'a`b'],
            'multiple embedded backticks' => ['`a``b``c`', 'a`b`c'],
        ]);
    }
}
